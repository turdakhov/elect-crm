<?php

use Livewire\Volt\Component;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;

new class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    #[Computed]
    public function products(): Collection
    {
        // Получаем все товары из смет
        $estimateProducts = $this->project->productSets()
            ->with('items.product')
            ->get()
            ->flatMap(fn($set) => $set->items)
            ->groupBy('product_id')
            ->map(fn($items) => [
                'product' => $items->first()->product,
                'estimate_quantity' => $items->sum('quantity'),
            ]);

        // Получаем все товары из закупов
        $purchaseProducts = $this->project->purchases()
            ->with('items.product')
            ->get()
            ->flatMap(fn($purchase) => $purchase->items)
            ->groupBy('product_id')
            ->map(fn($items) => [
                'product' => $items->first()->product,
                'purchase_quantity' => $items->sum('quantity'),
            ]);

        // Объединяем все товары
        $allProductIds = $estimateProducts->keys()->merge($purchaseProducts->keys())->unique();

        return $allProductIds->map(function ($productId) use ($estimateProducts, $purchaseProducts) {
            $estimate = $estimateProducts->get($productId);
            $purchase = $purchaseProducts->get($productId);

            $estimateQty = $estimate['estimate_quantity'] ?? 0;
            $purchaseQty = $purchase['purchase_quantity'] ?? 0;
            $remainder = $estimateQty - $purchaseQty;

            // Определяем цвет
            $color = 'red'; // по умолчанию красный (недостаточно закуплено)
            if ($estimateQty == 0 && $purchaseQty > 0) {
                $color = 'yellow'; // куплено, но нет в сметах
            } elseif ($purchaseQty >= $estimateQty && $estimateQty > 0) {
                $color = 'green'; // закуплено достаточно
            }

            return [
                'product' => $estimate['product'] ?? $purchase['product'],
                'estimate_quantity' => $estimateQty,
                'purchase_quantity' => $purchaseQty,
                'remainder' => $remainder,
                'color' => $color,
            ];
        })->sortBy(function ($item) {
            // Сначала по цвету (red -> green -> yellow), потом по названию
            $colorOrder = ['red' => 1, 'green' => 2, 'yellow' => 3];
            return [$colorOrder[$item['color']], $item['product']->name];
        })->values();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Сводка товаров</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
        </div>

        @if ($this->products->count() > 0)
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Товар</th>
                            <th scope="col" class="px-6 py-3 text-center">В сметах</th>
                            <th scope="col" class="px-6 py-3 text-center">Закуплено</th>
                            <th scope="col" class="px-6 py-3 text-center">Остаток</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->products as $item)
                        <tr wire:key='product-{{ $item['product']->id }}'
                            class="border-b dark:border-gray-700
                                @if($item['color'] === 'green') bg-green-50 dark:bg-green-900/20
                                @elseif($item['color'] === 'yellow') bg-yellow-50 dark:bg-yellow-900/20
                                @else bg-red-50 dark:bg-red-900/20
                                @endif">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $item['product']->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ $item['estimate_quantity'] }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ $item['purchase_quantity'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold
                                    @if($item['color'] === 'green') text-green-700 dark:text-green-400
                                    @elseif($item['color'] === 'yellow') text-yellow-700 dark:text-yellow-400
                                    @else text-red-700 dark:text-red-400
                                    @endif">
                                @if($item['remainder'] > 0)
                                -{{ $item['remainder'] }}
                                @elseif($item['remainder'] < 0)
                                    +{{ abs($item['remainder']) }}
                                    @else
                                    0
                                    @endif
                                    </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-200 dark:bg-green-900/40 border border-green-300 dark:border-green-700 rounded"></div>
                <span>Закуплено достаточно</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-yellow-200 dark:bg-yellow-900/40 border border-yellow-300 dark:border-yellow-700 rounded"></div>
                <span>Закуплено, но нет в сметах</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-red-200 dark:bg-red-900/40 border border-red-300 dark:border-red-700 rounded"></div>
                <span>Закуплено недостаточно</span>
            </div>
        </div>
        @else
        <flux:callout>
            <flux:callout.heading>Нет данных</flux:callout.heading>
            <p>В проекте пока нет товаров в сметах или закупах.</p>
        </flux:callout>
        @endif
    </div>
</div>