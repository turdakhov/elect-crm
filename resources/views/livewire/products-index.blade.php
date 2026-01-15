<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    #[\Livewire\Attributes\On('search')]
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Product::query()->latest();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return [
            'products' => $query->paginate(15),
        ];
    }

    public function delete(Product $product)
    {
        $product->delete();
        session()->flash('message', 'Товар успешно удален!');
    }
}; ?>

<div x-data="{ selectedImage: null, selectedName: null }">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <flux:button variant="primary" color="green" icon="plus" :href="route('products.create')">
                Добавить товар
            </flux:button>
            <div class="flex-1 max-w-md">
                <flux:input wire:model.live="search" placeholder="Поиск товара..." icon="magnifying-glass" />
            </div>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            @if ($products->hasPages())
            <div class="m-4">
                {{ $products->links() }}
            </div>
            @endif

            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">ID</th>
                            <th scope="col" class="px-6 py-3 text-center">Фото</th>
                            <th scope="col" class="px-6 py-3">Название</th>
                            <th scope="col" class="px-6 py-3">Описание</th>
                            <th scope="col" class="px-6 py-3">Цена</th>
                            <th scope="col" class="px-6 py-3">Единица</th>
                            <th scope="col" class="px-6 py-3">URL</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                        <tr wire:key='{{ $product->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 align-middle">{{ $product->id }}</td>
                            <td class="px-6 py-4 text-center align-middle">
                                @if ($product->file && $product->file->path)
                                <button 
                                    type="button"
                                    @click="selectedImage = '{{ Storage::url($product->file->path) }}'; selectedName = '{{ $product->name }}'"
                                    class="cursor-pointer hover:opacity-75 transition-opacity"
                                >
                                    <img src="{{ Storage::url($product->file->path) }}"
                                        alt="{{ $product->name }}"
                                        style="max-width: 100px; max-height: 100px; width: auto; height: auto; object-fit: contain;">
                                </button>
                                @else
                                <span class="text-gray-400">Нет фото</span>
                                @endif
                            </td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-middle">
                                {{ $product->name }}
                            </th>
                            <td class="px-6 py-4 align-middle">{{ Str::limit($product->description, 50) }}</td>
                            <td class="px-6 py-4 align-middle">{{ number_format($product->approximate_price, 2) }}</td>
                            <td class="px-6 py-4 align-middle">{{ $product->unit }}</td>
                            <td class="px-6 py-4 align-middle">
                                @if($product->url)
                                <a href="{{ $product->url }}" target="_blank" class="text-blue-600 hover:underline">Ссылка</a>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex gap-2 justify-end">
                                    <flux:button size="xs" color="blue" icon="pencil" :href="route('products.edit', $product)">
                                        Редактировать
                                    </flux:button>
                                    <flux:button size="xs" icon="trash" wire:click="delete({{ $product }})"
                                        onclick="return confirm('Вы уверены?')">
                                        Удалить
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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