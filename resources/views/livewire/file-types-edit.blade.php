<?php

use App\Models\FileType;
use Livewire\Volt\Component;

new class extends Component {
    public FileType $file_type;
    public $name;
    public $description;

    public function mount(FileType $file_type)
    {
        $this->file_type = $file_type;
        $this->name = $file_type->name;
        $this->description = $file_type->description;
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:file_types,name,' . $this->file_type->id,
            'description' => 'nullable|string|max:255',
        ]);

        $this->file_type->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()->flash('message', 'Тип файла успешно обновлен!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                <form wire:submit.prevent="submit" class="space-y-6">
                    <flux:input wire:model.defer='name' label="Наименование" required />
                    <flux:textarea wire:model.defer='description' label="Описание" />

                    <div class="flex items-center gap-4">
                        <div class="p-3">
                            <flux:button type="submit" variant="primary" color="green" icon="pencil">Обновить</flux:button>
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