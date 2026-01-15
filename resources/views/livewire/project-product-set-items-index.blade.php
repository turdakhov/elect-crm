<?php

use Livewire\Volt\Component;
use App\Models\ProjectProductSet;
use Illuminate\Support\Str;

new class extends Component {
    public ProjectProductSet $projectProductSet;

    public function with(): array
    {
        return [
            'items' => $this->projectProductSet->items()->latest()->get(),
        ];
    }

    public function mount(ProjectProductSet $projectProductSet)
    {
        $this->projectProductSet = $projectProductSet;
    }

    public function deleteItem($itemId)
    {
        \App\Models\ProjectProductSetItem::find($itemId)?->delete();
        session()->flash('message', 'Товар удален из набора!');
    }

    public function exportPdf()
    {
        $items = $this->projectProductSet->items()->latest()->get()->map(function ($item) {
            if ($item->product->file && $item->product->file->path) {
                $filePath = storage_path('app/public/' . $item->product->file->path);
                if (file_exists($filePath)) {
                    $imageData = base64_encode(file_get_contents($filePath));
                    $mimeType = mime_content_type($filePath);
                    $item->product->imageBase64 = "data:$mimeType;base64,$imageData";
                } else {
                    $item->product->imageBase64 = null;
                }
            } else {
                $item->product->imageBase64 = null;
            }
            return $item;
        });

        $pdf = \PDF::loadView('pdfs.project-product-set', [
            'productSet' => $this->projectProductSet,
            'items' => $items,
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
                            <th scope="col" class="px-6 py-3">Количество</th>
                            <th scope="col" class="px-6 py-3">Комментарий</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                        <tr wire:key='{{ $item->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $item->product->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($item->product->file && $item->product->file->path)
                                <button
                                    type="button"
                                    @click="selectedImage = '{{ Storage::url($item->product->file->path) }}'; selectedName = '{{ $item->product->name }}'"
                                    class="cursor-pointer hover:opacity-75 transition-opacity">
                                    <img src="{{ Storage::url($item->product->file->path) }}"
                                        alt="{{ $item->product->name }}"
                                        style="max-width: 100px; max-height: 100px; width: auto; height: auto; object-fit: contain;">
                                </button>
                                @else
                                <span class="text-gray-400">Нет фото</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {{ $item->quantity }} {{ $item->product->unit ?? '' }}
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-200">
                                {{ $item->comment ?? '—' }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('project-product-set-items.edit', $item)">
                                    Редактировать
                                </flux:button>
                                <flux:button size="xs" color="rose" icon="trash" wire:click="deleteItem({{ $item->id }})"
                                    onclick="return confirm('Удалить товар из сметы?')">
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