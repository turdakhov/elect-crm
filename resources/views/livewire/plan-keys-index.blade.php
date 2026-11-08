<?php

use App\Models\PlanKey;
use App\Models\Product;
use App\Models\ProjectProductSetItem;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    #[Url]
    public bool $unmapped = false;

    #[Url]
    public string $search = '';

    /** Какой ключ сейчас привязывают и что набрано в поиске товара. */
    public ?int $editing = null;

    public string $productSearch = '';

    #[Computed]
    public function keys()
    {
        return PlanKey::with('product')
            ->when($this->unmapped, fn ($q) => $q->whereNull('product_id')->where('skip', false))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('key', 'like', "%{$this->search}%")))
            ->orderBy('section')->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->productSearch !== '', fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%"))
            ->orderBy('name')->limit(20)->get();
    }

    public function edit(int $id): void
    {
        $this->editing = $id;
        $this->productSearch = '';
    }

    public function choose(int $productId): void
    {
        $key = $this->editing ? PlanKey::find($this->editing) : null;
        if ($key && Product::whereKey($productId)->exists()) {
            $key->update(['product_id' => $productId]);
            // Сметы выдают, не дожидаясь привязки: строки этого ключа без товара
            // получают его сейчас. Выбранный в строке руками не трогаем.
            $filled = ProjectProductSetItem::where('plan_key', $key->key)->whereNull('product_id')->update(['product_id' => $productId]);
            if ($filled) {
                session()->flash('message', 'Товар подставлен в строки смет без товара: '.$filled.'.');
            }
        }
        $this->editing = null;
        $this->productSearch = '';
        unset($this->keys);
    }

    /** «Не закупаем» — строки ключа в смету не попадают: заглушки, которых нет в продаже. */
    public function toggleSkip(int $id): void
    {
        $key = PlanKey::find($id);
        $key?->update(['skip' => ! $key->skip]);
        unset($this->keys);
    }

    public function unlink(int $id): void
    {
        PlanKey::whereKey($id)->update(['product_id' => null]);
        unset($this->keys);
    }

    public function cancel(): void
    {
        $this->editing = null;
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <h1 class="text-2xl font-bold">Ключи 3D-плана → товары</h1>
            <p class="text-gray-600 dark:text-gray-400">
                3D-план считает «кабель ВВГнг 3×2.5» и «розетку», а не бренд. Какой товар под этим покупать — здесь, один раз на все проекты.
                Уже выданные сметы смена товара не трогает.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="w-72"><flux:input wire:model.live.debounce.300ms="search" placeholder="Поиск по ключу" icon="magnifying-glass" /></div>
            <flux:checkbox wire:model.live="unmapped" label="Только непривязанные" />
        </div>

        @if (session()->has('message'))
            <flux:callout icon="check-circle" color="green"><flux:callout.heading>{{ session('message') }}</flux:callout.heading></flux:callout>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Раздел</th>
                        <th class="px-4 py-3">В 3D-плане</th>
                        <th class="px-4 py-3">Ед.</th>
                        <th class="px-4 py-3">Товар</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->keys as $key)
                        <tr wire:key="key-{{ $key->id }}" @class(['border-b border-gray-200 align-top dark:border-gray-700', 'opacity-50' => $key->skip])>
                            <td class="px-4 py-3">{{ $key->section }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $key->name }}<div class="text-xs text-gray-400">{{ $key->key }}</div></td>
                            <td class="px-4 py-3">{{ $key->unit }}</td>
                            <td class="px-4 py-3">
                                @if ($editing === $key->id)
                                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Найти товар" autofocus />
                                    <ul class="mt-2 max-h-64 overflow-y-auto rounded border border-gray-200 dark:border-gray-700">
                                        @forelse ($this->products as $product)
                                            <li wire:key="p-{{ $product->id }}">
                                                <button type="button" wire:click="choose({{ $product->id }})"
                                                    class="w-full px-3 py-1.5 text-left hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    {{ $product->name }} <span class="text-xs text-gray-400">{{ $product->unit }}</span>
                                                </button>
                                            </li>
                                        @empty
                                            <li class="px-3 py-1.5 text-gray-400">Не найдено. <a class="text-blue-600 underline" href="{{ route('products.create') }}">Завести товар</a></li>
                                        @endforelse
                                    </ul>
                                @elseif ($key->product)
                                    {{ $key->product->name }}
                                    @if ($key->product->unit !== $key->unit)
                                        <div class="text-xs text-amber-600">единица товара «{{ $key->product->unit }}», а 3D-план считает в «{{ $key->unit }}»</div>
                                    @endif
                                @elseif ($key->skip)
                                    <span class="text-gray-500">не закупаем</span>
                                @else
                                    <span class="text-red-600">не привязан</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if ($editing === $key->id)
                                    <flux:button size="xs" variant="ghost" wire:click="cancel">Отмена</flux:button>
                                @else
                                    <flux:button size="xs" color="blue" icon="link" wire:click="edit({{ $key->id }})">{{ $key->product ? 'Сменить' : 'Привязать' }}</flux:button>
                                    @if ($key->product)
                                        <flux:button size="xs" variant="ghost" wire:click="unlink({{ $key->id }})">Отвязать</flux:button>
                                    @endif
                                    <flux:button size="xs" variant="ghost" wire:click="toggleSkip({{ $key->id }})">{{ $key->skip ? 'Закупаем' : 'Не закупаем' }}</flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center">Ключей нет — они появляются с первой отправкой из 3D-плана.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
