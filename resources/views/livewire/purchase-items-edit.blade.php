<?php

use App\Models\Product;
use App\Models\PurchaseItem;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public PurchaseItem $purchaseItem;

    public $product_id;
    public $quantity;
    public $unit_price;
    public $search = '';
    public $display_value = '';

    public function mount(PurchaseItem $purchaseItem): void
    {
        $this->purchaseItem = $purchaseItem;
        $this->product_id = $purchaseItem->product_id;
        $this->quantity = $purchaseItem->quantity;
        $this->unit_price = $purchaseItem->unit_price;
        $this->search = $purchaseItem->product?->name ?? '';
        $this->display_value = $purchaseItem->product?->name ?? '';
    }

    public function submit(): void
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|integer|min:0',
        ]);

        $this->purchaseItem->update([
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
        ]);

        session()->flash('message', 'Позиция обновлена.');
    }

    #[Computed]
    public function products(): \Illuminate\Support\Collection
    {
        return Product::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name']);
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        return $this->product_id ? Product::find($this->product_id) : null;
    }

    #[Computed]
    public function selectedProductName(): string
    {
        return $this->selectedProduct?->name ?? '';
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
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Редактировать позицию закупа</flux:heading>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <form wire:submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2" x-data="{ open: false, display: '' }" x-init="display = '{{ addslashes($display_value ?: ($search ?: ($selectedProductName ?? ''))) }}'" x-effect="display = $wire.get('display_value') || display">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Поиск товара</label>
                            <input
                                type="text"
                                class="w-full rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                                x-model="display"
                                placeholder="Начните вводить название..."
                                x-on:focus="open = true"
                                x-on:keydown.escape="open = false"
                                x-on:input="$wire.set('search', display)"
                            />
                            <div class="rounded-md border border-neutral-200 dark:border-neutral-700 max-h-64 overflow-y-auto" x-show="open" x-transition>
                                @forelse ($this->products as $product)
                                    <button
                                        type="button"
                                        wire:key="product-{{ $product->id }}"
                                        wire:click="selectProduct({{ $product->id }})"
                                        x-on:click="display='{{ addslashes($product->name) }}'; open = false"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800"
                                    >
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
                        <flux:input type="number" wire:model="quantity" label="Количество" class="max-w-xs" />
                        <flux:input type="number" wire:model="unit_price" label="Цена за единицу (₽)" class="max-w-xs" />
                        <div class="flex items-center gap-4">
                            <flux:button variant="filled" icon="arrow-left" :href="route('purchases.items.index', $purchaseItem->purchase)">Назад</flux:button>
                            <div class="p-3">
                                <flux:button type="submit" variant="primary" color="green" icon="pencil">Обновить</flux:button>
                            </div>
                            @if (session()->has('message'))
                            <div class="w-full">
                                <flux:callout icon="bell-alert" class="mt-0">
                                    <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                                </flux:callout>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
