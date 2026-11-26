<?php

use Livewire\Volt\Component;
use App\Models\ProjectProductSet;
use Illuminate\Support\Str;

new class extends Component {
    public ProjectProductSet $projectProductSet;

    public function with(): array
    {
        $sections = $this->projectProductSet->sections();

        return [
            'sections' => $sections,
            'items' => $sections->flatMap(fn ($s) => $s['items']),
            'total' => round($sections->sum('total'), 2),
            'unpriced' => ProjectProductSet::unpriced($sections),
        ];
    }

    public function mount(ProjectProductSet $projectProductSet)
    {
        $this->projectProductSet = $projectProductSet;
    }

    public function deleteItem($itemId)
    {
        $this->projectProductSet->items()->whereKey($itemId)->first()?->delete();
        session()->flash('message', 'Товар удален из набора!');
    }

    public function exportPdf()
    {
        $sections = $this->projectProductSet->sections();
        foreach ($sections->flatMap(fn ($s) => $s['items']) as $item) {
            if (!$item->product) {
                continue;
            }
            $item->product->imageBase64 = null;
            if ($item->product->file && $item->product->file->path) {
                $filePath = storage_path('app/public/' . $item->product->file->path);
                if (file_exists($filePath)) {
                    $imageData = base64_encode(file_get_contents($filePath));
                    $mimeType = mime_content_type($filePath);
                    $item->product->imageBase64 = "data:$mimeType;base64,$imageData";
                }
            }
        }

        $pdf = \PDF::loadView('pdfs.project-product-set', [
            'productSet' => $this->projectProductSet,
            'sections' => $sections,
            'total' => round($sections->sum('total'), 2),
            'unpriced' => ProjectProductSet::unpriced($sections),
        ])
            ->setOption('isRemoteEnabled', true)
            ->setOption('chroot', public_path())
            ->setOption('encoding', 'UTF-8')
            ->setOption('enable_local', true);

        // Сохраняем PDF во временный файл
        $setSlug = Str::slug($this->projectProductSet->name, '-');
        $projectSlug = Str::slug($this->projectProductSet->project->name, '-');
        $fallback = 'smeta-' . $this->projectProductSet->id;
        $filename = trim('smeta-' . ($setSlug ?: '') . '-' . ($projectSlug ?: ''), '-');
        $filename = ($filename !== 'smeta' ? $filename : $fallback) . '.pdf';
        $path = storage_path('app/temp/' . $filename);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $pdf->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}; ?>

<div x-data="{ selectedImage: null, selectedName: null }">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Товары в смете</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $projectProductSet->project->name }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" icon="plus" :href="route('project-product-set.items.create', $projectProductSet)">
                    Добавить товар
                </flux:button>
                <flux:button color="emerald" icon="document-text" wire:click="exportPdf">
                    Скачать PDF
                </flux:button>
                <flux:button color="lime" icon="table-cells" :href="route('project-product-set.export-xlsx', $projectProductSet)">
                    Скачать Excel
                </flux:button>
            </div>
        </div>

        @if ($projectProductSet->fromPlan())
        <flux:callout icon="cube">
            <flux:callout.heading>{{ $projectProductSet->name }} — выдана {{ $projectProductSet->created_at->format('d.m.Y H:i') }}{{ $projectProductSet->issuedBy ? ', '.$projectProductSet->issuedBy->name : '' }}</flux:callout.heading>
            <p>Смету можно править: изменения проекта придут доп-сметой, а ручные правки ею не перепишутся.
                @if ($items->whereNull('product_id')->count())
                    Строк без товара: {{ $items->whereNull('product_id')->count() }} — выберите товар в строке или привяжите ключ в <a class="underline" href="{{ route('plan-keys.index', ['unmapped' => 1]) }}">ключах 3D-плана</a>.
                @endif
            </p>
        </flux:callout>
        @endif

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        @php
            $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', ' '), '0'), ',');
            $money = fn ($v) => number_format($v, fmod((float) $v, 1) == 0.0 ? 0 : 2, ',', ' ') . ' ₸';
            $n = 0;
        @endphp
        @if ($items->count() > 0)
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-neutral-200 bg-white px-5 py-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500">Итого по смете</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $money($total) }}</div>
            </div>
            <div class="rounded-xl border border-neutral-200 bg-white px-5 py-4 dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-xs uppercase tracking-wide text-zinc-500">Позиций</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $items->count() }} <span class="text-sm font-normal text-zinc-500">в разделах: {{ $sections->count() }}</span></div>
            </div>
            <div @class(['rounded-xl border px-5 py-4', 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40' => $unpriced, 'border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' => ! $unpriced])>
                <div class="text-xs uppercase tracking-wide text-zinc-500">Без цены</div>
                <div @class(['mt-1 text-2xl font-semibold tabular-nums', 'text-amber-700 dark:text-amber-400' => $unpriced, 'text-zinc-900 dark:text-white' => ! $unpriced])>{{ $unpriced }}
                    <span class="text-sm font-normal text-zinc-500">{{ $unpriced ? 'в итог не вошли' : 'все строки посчитаны' }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-zinc-700 dark:text-zinc-300">
                    <thead class="border-b border-neutral-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-neutral-700 dark:bg-zinc-800/60">
                        <tr>
                            <th scope="col" class="w-10 px-3 py-3 text-right">№</th>
                            <th scope="col" class="w-14 px-2 py-3"></th>
                            <th scope="col" class="px-3 py-3 text-left">Товар</th>
                            <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Кол-во</th>
                            <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Цена</th>
                            <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Сумма</th>
                            <th scope="col" class="w-20 px-3 py-3"></th>
                        </tr>
                    </thead>
                    @foreach ($sections as $section)
                    <tbody wire:key="section-{{ $loop->index }}">
                        @if ($sections->count() > 1)
                        <tr class="border-b border-neutral-200 bg-zinc-100/70 dark:border-neutral-700 dark:bg-zinc-800">
                            <th colspan="5" scope="colgroup" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">
                                {{ $section['title'] }} <span class="ml-1 font-normal normal-case text-zinc-400">· {{ $section['items']->count() }}</span>
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-semibold tabular-nums whitespace-nowrap text-zinc-600 dark:text-zinc-300">{{ $money($section['total']) }}</th>
                            <th></th>
                        </tr>
                        @endif
                        @foreach ($section['items'] as $item)
                        @php $n++; $price = $item->unitPrice(); $amount = $item->amount(); @endphp
                        <tr wire:key="item-{{ $item->id }}" class="border-b border-neutral-100 last:border-b-0 hover:bg-zinc-50 dark:border-neutral-800 dark:hover:bg-zinc-800/50">
                            <td class="px-3 py-2 text-right text-xs tabular-nums text-zinc-400">{{ $n }}</td>
                            <td class="px-2 py-2">
                                @if ($item->product && $item->product->file && $item->product->file->path)
                                <button type="button"
                                    @click="selectedImage = @js(Storage::url($item->product->file->path)); selectedName = @js($item->product->name)"
                                    class="block size-10 cursor-pointer overflow-hidden rounded-md border border-neutral-200 bg-white transition hover:opacity-75 dark:border-neutral-700">
                                    <img src="{{ Storage::url($item->product->file->path) }}" alt="{{ $item->product->name }}" class="size-full object-contain">
                                </button>
                                @else
                                <div class="size-10 rounded-md border border-dashed border-neutral-200 dark:border-neutral-700"></div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($item->product)
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $item->product->name }}</div>
                                @else
                                <div class="font-medium text-amber-600">без товара</div>
                                @endif
                                @if ($item->comment)
                                <div class="text-xs text-zinc-500">{{ $item->comment }}</div>
                                @endif
                            </td>
                            <td @class(['px-3 py-2 text-right tabular-nums whitespace-nowrap', 'text-red-600' => $item->quantity < 0])>
                                {{ $qty($item->quantity) }} {{ $item->product?->unit ?? "" }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums whitespace-nowrap text-zinc-500">
                                {{ $price ? $money($price) : '—' }}
                            </td>
                            <td @class(['px-3 py-2 text-right font-medium tabular-nums whitespace-nowrap', 'text-red-600' => ($amount ?? 0) < 0, 'text-zinc-900 dark:text-white' => ($amount ?? 0) >= 0])>
                                @if ($price)
                                {{ $money($amount) }}
                                @else
                                <span class="text-xs font-normal text-amber-600">нет цены</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil" :href="route('project-product-set-items.edit', $item)" title="Редактировать" />
                                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteItem({{ $item->id }})"
                                        onclick="return confirm('Удалить товар из сметы?')" title="Удалить" />
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @endforeach
                    <tfoot class="border-t-2 border-neutral-200 bg-zinc-50 dark:border-neutral-700 dark:bg-zinc-800/60">
                        <tr>
                            <td colspan="5" class="px-3 py-3 text-right font-semibold text-zinc-900 dark:text-white">
                                Итого
                                @if ($unpriced)
                                <span class="ml-2 text-xs font-normal text-amber-600">строки без цены не вошли: {{ $unpriced }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right text-base font-semibold tabular-nums whitespace-nowrap text-zinc-900 dark:text-white">{{ $money($total) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
        <flux:callout>
            <flux:callout.heading>Товаров нет</flux:callout.heading>
            <p>В наборе еще нет товаров. Добавьте товар, чтобы начать.</p>
        </flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:button variant="ghost" :href="route('projects.product-set.index', $projectProductSet->project)">Вернуться к набору</flux:button>
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