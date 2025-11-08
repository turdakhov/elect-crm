<?php

use App\Models\FileType;
use Faker\Core\File;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public $description;

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:file_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        FileType::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()->flash('message', 'Тип файла успешно создан!');
        $this->redirectRoute('file-types.index');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="p-6">
                <form wire:submit.prevent="submit" class="space-y-6">
                    <flux:input wire:model='name' label="Наименование" />
                    <flux:textarea wire:model='description' label="Описание" />


                    <div class="flex items-center gap-4">
                        <flux:button type="submit" variant="primary" color="green" icon="plus">Создать</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>