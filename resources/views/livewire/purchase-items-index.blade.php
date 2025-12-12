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

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Позиции закупа #{{ $purchase->id }} — {{ $purchase->project?->name }}</flux:heading>
        <div class="flex items-center gap-4">
            <flux:button variant="filled" icon="arrow-left" :href="route('projects.purchases.index', $purchase->project)">Назад к закупам</flux:button>
            <flux:button variant="primary" color="green" icon="plus" :href="route('purchases.items.create', $purchase)">Добавить позицию</flux:button>
        </div>

        @if (session()->has('message'))
        <div class="w-full">
            <flux:callout icon="bell-alert" class="mt-0">
                <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
            </flux:callout>
        </div>
        @endif

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Товар</th>
                            <th class="px-6 py-3">Кол-во</th>
                            <th class="px-6 py-3">Цена за ед.</th>
                            <th class="px-6 py-3">Сумма</th>
                            <th class="px-6 py-3 text-right">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $purchaseItem)
                        <tr wire:key="purchase-item-{{ $purchaseItem->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">{{ $purchaseItem->product?->name }}</td>
                            <td class="px-6 py-4">{{ $purchaseItem->quantity }}</td>
                            <td class="px-6 py-4">{{ number_format($purchaseItem->unit_price, 0, ',', ' ') }} ₽</td>
                            <td class="px-6 py-4">{{ number_format($purchaseItem->total_price, 0, ',', ' ') }} ₽</td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('purchase-items.edit', $purchaseItem)">Редактировать</flux:button>
                                <flux:button size="xs" color="red" icon="trash" wire:click="delete({{ $purchaseItem->id }})" confirm="Вы уверены, что хотите удалить эту позицию?">Удалить</flux:button>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Нет добавленных позиций.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
