<?php

use Livewire\Volt\Component;
use App\Models\ProjectProductSetItem;
use App\Models\Product;
use Livewire\Attributes\Computed;

new class extends Component {
    public ProjectProductSetItem $projectProductSetItem;
    public $product_id;
    public $quantity;
    public $comment = '';
    public $search = '';
    public $display_value = '';

    public function mount(ProjectProductSetItem $projectProductSetItem)
    {
        $this->projectProductSetItem = $projectProductSetItem;
        $this->product_id = $projectProductSetItem->product_id;
        $this->quantity = $projectProductSetItem->quantity;
        $this->display_value = $projectProductSetItem->product->name;
        $this->comment = $projectProductSetItem->comment;
    }

    public function selectProduct(int $productId): void
    {
        $this->product_id = $productId;
        $this->display_value = Product::find($productId)?->name ?? '';
        $this->search = '';
    }

    public function clearProduct(): void
    {
        $this->product_id = null;
        $this->display_value = '';
        $this->search = '';
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->whereNotIn('id', $this->excludedProductIds)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function selectedProduct()
    {
        return $this->product_id ? Product::find($this->product_id) : null;
    }

    #[Computed]
    public function selectedProductName(): string
    {
        return $this->selectedProduct?->name ?? '';
    }

    #[Computed]
    public function excludedProductIds(): array
    {
        return $this->projectProductSetItem
            ->projectProductSet
            ->items()
            ->where('id', '!=', $this->projectProductSetItem->id)
            ->pluck('product_id')
            ->all();
    }

    public function submit()
    {
        $this->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                'unique:project_product_set_items,product_id,' . $this->projectProductSetItem->id . ',id,project_product_set_id,' . $this->projectProductSetItem->project_product_set_id,
            ],
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ], [
            'product_id.unique' => 'Этот товар уже добавлен в смету.',
            'quantity.max' => 'Количество не должно превышать 10000.',
        ]);

        $this->projectProductSetItem->update([
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'comment' => $this->comment,
        ]);

        session()->flash('message', 'Товар успешно обновлен!');
        return redirect()->route('project-product-set.items.index', $this->projectProductSetItem->projectProductSet);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <h1 class="text-2xl font-bold">Редактировать товар</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $projectProductSetItem->projectProductSet->project->name }}</p>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <form wire:submit="submit" class="space-y-6">
                <div class="space-y-2" x-data="{ open: false, display: '' }" x-init="display = '{{ addslashes($display_value ?: ($search ?: ($selectedProductName ?? ''))) }}'" x-effect="display = $wire.get('display_value') || display">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Поиск товара</label>
                    <input
                        type="text"
                        class="w-full rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        x-model="display"
                        placeholder="Начните вводить название..."
                        x-on:focus="open = true"
                        x-on:keydown.escape="open = false"
                        x-on:input="$wire.set('search', display)" />
                    <div class="rounded-md border border-neutral-200 dark:border-neutral-700 max-h-64 overflow-y-auto" x-show="open" x-transition>
                        @forelse ($this->products as $product)
                        <button
                            type="button"
                            wire:key="product-{{ $product->id }}"
                            wire:click="selectProduct({{ $product->id }})"
                            x-on:click="display='{{ addslashes($product->name) }}'; open = false"
                            class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                            {{ $product->name }}
                        </button>
                        @empty
                        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">Ничего не найдено</div>
                        @endforelse
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        @if ($this->selectedProduct)
                        Выбран: <span class="font-medium">{{ $this->selectedProduct->name }}</span>
                        <button type="button" class="ml-2 text-red-600" wire:click="clearProduct" x-on:click="display = ''">Очистить</button>
                        @else
                        Товар не выбран
                        @endif
                    </div>
                </div>

                <flux:field>
                    <flux:label for="quantity">Количество</flux:label>
                    <flux:input id="quantity" type="number" wire:model="quantity" min="1" placeholder="Количество"></flux:input>
                    <flux:error name="quantity" />
                </flux:field>

                <flux:field>
                    <flux:label for="comment">Комментарий</flux:label>
                    <flux:textarea
                        id="comment"
                        wire:model="comment"
                        placeholder="Комментарий к позиции (необязательно)"
                        rows="3"></flux:textarea>
                    <flux:error name="comment" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">Сохранить</flux:button>
                    <flux:button variant="ghost" :href="route('project-product-set.items.index', $projectProductSetItem->projectProductSet)">Отмена</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>