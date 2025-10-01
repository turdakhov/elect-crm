<?php

use Livewire\Volt\Component;
use \App\Models\Complex;

new class extends Component {
    public $id;
    public $name;
    public $address;
    public $description;
    public Complex $complex;

    public function mount(Complex $complex)
    {
        $this->id = $complex->id;
        $this->name = $complex->name;
        $this->address = $complex->address;
        $this->description = $complex->description;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255,unique:complexes,name,' . $this->id,
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->complex->update([
            'name' => $this->name,
            'address' => $this->address,
            'description' => $this->description,
        ]);

        session()->flash('message', 'ЖК успешно изменен!');

        $this->redirect(route('complexes.index'));
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

    <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

        <div class="relative overflow-x-auto">
            <div>
                <form wire:submit.prevent="update" class="space-y-6 p-6">
                    <flux:input wire:model='name' label="Название ЖК" />
                    <flux:input wire:model='address' label="Адрес" />
                    <flux:textarea wire:model='description' label="Описание" />
                    <flux:button variant="primary" color="green" type="submit" icon="plus">Сохранить изменения</flux:button>
                </form>
            </div>

        </div>
    </div>
</div>

