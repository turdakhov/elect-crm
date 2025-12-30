<?php

use Livewire\Volt\Component;
use App\Models\ProjectProductSet;
use App\Models\Product;
use Livewire\Attributes\Computed;

new class extends Component {
    public ProjectProductSet $projectProductSet;
    public $product_id;
    public $quantity = 1;
    public $search = '';
    public $display_value = '';

    public function mount(ProjectProductSet $projectProductSet)
    {
        $this->projectProductSet = $projectProductSet;
        $this->display_value = $this->selectedProductName;
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->whereNotIn('id', $this->excludedProductIds)
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
        return $this->projectProductSet
            ->items()
            ->pluck('product_id')
            ->all();
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::find($productId);

        $this->product_id = $productId;
        $this->search = '';
        $this->display_value = $product?->name ?? '';
    }

    public function clearProduct(): void
    {
        $this->product_id = null;
        $this->search = '';
        $this->display_value = '';
    }

    public function submit()
    {
        $this->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                'unique:project_product_set_items,product_id,NULL,id,project_product_set_id,'.$this->projectProductSet->id,
            ],
            'quantity' => 'required|integer|min:1|max:10000',
        ], [
            'product_id.unique' => 'Этот товар уже добавлен в смету.',
            'quantity.max' => 'Количество не должно превышать 10000.',
        ]);

        \App\Models\ProjectProductSetItem::create([
            'project_product_set_id' => $this->projectProductSet->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
        ]);

        session()->flash('message', 'Товар добавлен в смету!');
        return redirect()->route('project-product-set.items.index', $this->projectProductSet);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <h1 class="text-2xl font-bold">Добавить товар в смету</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $projectProductSet->project->name }}</p>
        </div>

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

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">Добавить</flux:button>
                    <flux:button variant="ghost" :href="route('project-product-set.items.index', $projectProductSet)">Отмена</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
