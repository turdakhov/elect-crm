<?php

use App\Models\Product;
use App\Models\File;
use App\Models\FileType;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public Product $product;
    public $name;
    public $description;
    public $approximate_price;
    public $unit;
    public $url;
    public $comments;
    public $uploaded_file;

    public function mount(Product $product)
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->approximate_price = $product->approximate_price;
        $this->unit = $product->unit;
        $this->url = $product->url;
        $this->comments = $product->comments;
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'approximate_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:255',
            'url' => 'nullable|url',
            'comments' => 'nullable|string',
            'uploaded_file' => 'nullable|file|max:10240',
        ]);

        $updateData = [
            'name' => $this->name,
            'description' => $this->description,
            'approximate_price' => $this->approximate_price,
            'unit' => $this->unit,
            'url' => $this->url,
            'comments' => $this->comments,
        ];

        $oldFileId = null;

        if ($this->uploaded_file) {
            // Запоминаем старый file_id перед обновлением
            $oldFileId = $this->product->file_id;

            $originalName = $this->uploaded_file->getClientOriginalName();
            $path = $this->uploaded_file->store('products', 'public');

            // Находим ID типа "картинка"
            $imageFileType = FileType::where('name', 'картинка')->first();

            $file = File::create([
                'name' => $this->name,
                'path' => $path,
                'original_name' => $originalName,
                'uploaded_by' => auth()->id(),
                'file_type_id' => $imageFileType?->id,
            ]);

            $updateData['file_id'] = $file->id;
        }

        $this->product->update($updateData);

        // Удаляем старый файл ПОСЛЕ успешного обновления продукта
        if ($oldFileId) {
            $oldFile = File::find($oldFileId);
            if ($oldFile) {
                Storage::disk('public')->delete($oldFile->path);
                $oldFile->delete();
            }
        }

        session()->flash('message', 'Товар успешно обновлен.');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                <form wire:submit.prevent="submit" class="space-y-6">
                    <flux:input wire:model='name' label="Название" required />
                    <flux:textarea wire:model='description' label="Описание" />
                    <flux:input wire:model='approximate_price' label="Примерная цена" type="number" step="0.01" required />
                    <flux:input wire:model='unit' label="Единица измерения" required />
                    <flux:input wire:model='url' label="URL" type="url" />
                    <flux:textarea wire:model='comments' label="Комментарии" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Файл (изображение, документ)</label>

                        <div class="flex gap-4 items-start">
                            @if($product->file)
                            <div class="flex-1 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Текущий файл:</p>
                                @if(in_array(pathinfo($product->file->path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <img src="{{ Storage::url($product->file->path) }}" class="max-w-xs rounded-lg shadow-md mb-2" alt="Current file">
                                @endif
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $product->file->original_name }}</p>
                            </div>
                            @endif

                            @if ($uploaded_file)
                            <div class="flex-1 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Новый файл (превью):</p>
                                @if(in_array($uploaded_file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <img src="{{ $uploaded_file->temporaryUrl() }}" class="max-w-xs rounded-lg shadow-md" alt="Preview">
                                @else
                                <p class="text-sm text-gray-500">Файл: {{ $uploaded_file->getClientOriginalName() }}</p>
                                @endif
                            </div>
                            @endif
                        </div>

                        <input type="file" wire:model="uploaded_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 mt-4" />

                        <div wire:loading wire:target="uploaded_file" class="mt-2 text-sm text-blue-600">
                            Загрузка нового файла...
                        </div>

                        @error('uploaded_file')
                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="p-3">
                            <flux:button type="submit" variant="primary" color="green" icon="plus">Обновить</flux:button>
                        </div>
                        @if (session()->has('message'))
                        <div class="w-full" class="mt-0">
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