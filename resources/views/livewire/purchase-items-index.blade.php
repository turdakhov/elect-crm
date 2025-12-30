<?php

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Livewire\Volt\Component;

new class extends Component {
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $this->purchase = $purchase->loadMissing('project');
    }

    public function with(): array
    {
        return [
            'items' => PurchaseItem::query()
                ->with('product')
                ->where('purchase_id', $this->purchase->id)
                ->latest()
                ->get(),
        ];
    }

    public function delete(PurchaseItem $purchaseItem): void
    {
        if ($purchaseItem->purchase_id !== $this->purchase->id) {
            abort(404);
        }

        $purchaseItem->delete();
        session()->flash('message', 'Позиция удалена.');
    }
}; ?>

<div x-data="{ selectedImage: null, selectedName: null }">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Позиции закупа</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $purchase->project?->name }} — Закуп #{{ $purchase->id }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" icon="plus" :href="route('purchases.items.create', $purchase)">
                    Добавить позицию
                </flux:button>
                <flux:button variant="ghost" icon="arrow-left" :href="route('projects.purchases.index', $purchase->project)">
                    Назад
                </flux:button>
            </div>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        @if ($items->count() > 0)
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Товар</th>
                            <th scope="col" class="px-6 py-3 text-center">Фото</th>
                            <th scope="col" class="px-6 py-3">Кол-во</th>
                            <th scope="col" class="px-6 py-3">Цена за ед.</th>
                            <th scope="col" class="px-6 py-3">Сумма</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $purchaseItem)
                        <tr wire:key="purchase-item-{{ $purchaseItem->id }}"
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $purchaseItem->product?->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($purchaseItem->product->file && $purchaseItem->product->file->path)
                                <button 
                                    type="button"
                                    @click="selectedImage = '{{ Storage::url($purchaseItem->product->file->path) }}'; selectedName = '{{ $purchaseItem->product->name }}'"
                                    class="cursor-pointer hover:opacity-75 transition-opacity"
                                >
                                    <img src="{{ Storage::url($purchaseItem->product->file->path) }}"
                                        alt="{{ $purchaseItem->product->name }}"
                                        style="max-width: 100px; max-height: 100px; width: auto; height: auto; object-fit: contain;">
                                </button>
                                @else
                                <span class="text-gray-400">Нет фото</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $purchaseItem->quantity }}</td>
                            <td class="px-6 py-4">{{ number_format($purchaseItem->unit_price, 0, ',', ' ') }} ₽</td>
                            <td class="px-6 py-4">{{ number_format($purchaseItem->total_price, 0, ',', ' ') }} ₽</td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('purchase-items.edit', $purchaseItem)">
                                    Редактировать
                                </flux:button>
                                <flux:button size="xs" color="rose" icon="trash" wire:click="delete({{ $purchaseItem->id }})"
                                    onclick="return confirm('Удалить позицию?')">
                                    Удалить
                                </flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <flux:callout>
            <flux:callout.heading>Позиций нет</flux:callout.heading>
            <p>В закупе еще нет позиций. Добавьте позицию, чтобы начать.</p>
        </flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:button variant="ghost" :href="route('projects.purchases.index', $purchase->project)">Назад к закупам</flux:button>
        </div>
    </div>

    <!-- Image Modal -->
    <template x-if="selectedImage">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click="selectedImage = null">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 max-w-2xl max-h-[80vh] overflow-auto" @click.stop>
                <div class="flex flex-col items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedName"></h2>
                    <img :src="selectedImage" :alt="selectedName"
                         style="max-width: 100%; max-height: 60vh; width: auto; height: auto; object-fit: contain;">
                    <button type="button" @click="selectedImage = null"
                            class="px-4 py-2 bg-gray-200 dark:bg-neutral-700 rounded hover:bg-gray-300 dark:hover:bg-neutral-600 transition-colors">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
