<?php

use App\Models\Product;
use App\Models\File;
use App\Models\FileType;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $name;
    public $description;
    public $approximate_price;
    public $unit;
    public $url;
    public $comments;
    public $uploaded_file;

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'approximate_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:255',
            'url' => 'nullable|url',
            'comments' => 'nullable|string',
            'uploaded_file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $fileId = null;

        if ($this->uploaded_file) {
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

            $fileId = $file->id;
        }

        Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'approximate_price' => $this->approximate_price,
            'unit' => $this->unit,
            'url' => $this->url,
            'comments' => $this->comments,
            'file_id' => $fileId,
        ]);

        session()->flash('success', 'Товар успешно добавлен!');

        $this->redirectRoute('products.index');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <flux:input wire:model='name' label="Название" required />
                    <flux:textarea wire:model='description' label="Описание" />
                    <flux:input wire:model='approximate_price' label="Примерная цена" type="number" step="0.01" required />
                    <flux:input wire:model='unit' label="Единица измерения" required />
                    <flux:input wire:model='url' label="URL" type="url" />
                    <flux:textarea wire:model='comments' label="Комментарии" />
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Файл (изображение, документ)</label>
                        <input type="file" wire:model="uploaded_file" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" />
                        
                        <div wire:loading wire:target="uploaded_file" class="mt-2 text-sm text-blue-600">
                            Загрузка файла...
                        </div>
                        
                        @if ($uploaded_file)
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Превью:</p>
                            @if(in_array($uploaded_file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            <img src="{{ $uploaded_file->temporaryUrl() }}" class="max-w-xs rounded-lg shadow-md" alt="Preview">
                            @else
                            <p class="text-sm text-gray-500">Файл: {{ $uploaded_file->getClientOriginalName() }}</p>
                            @endif
                        </div>
                        @endif
                        
                        @error('uploaded_file') 
                        <span class="text-red-600 text-sm mt-1">{{ $message }}</span> 
                        @enderror
                    </div>

                    <flux:button wire:click="submit" variant="primary" color="green" type="button" icon="plus">
                        Создать
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>