<?php

use App\Models\Cable;
use Livewire\Volt\Component;

new class extends Component {
    public $name = '';

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Название обязательно.',
            'name.max' => 'Название не должно превышать 255 символов.',
        ]);

        Cable::create([
            'name' => $this->name,
        ]);

        session()->flash('message', 'Кабель успешно добавлен!');

        return redirect()->route('cables.index');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <div>
                        <h1 class="text-2xl font-bold">Добавить кабель</h1>
                    </div>

                    <form wire:submit="submit" class="space-y-6">
                        <flux:field>
                            <flux:label for="name">Название</flux:label>
                            <flux:input id="name" type="text" wire:model="name" placeholder="Введите название кабеля"></flux:input>
                            <flux:error name="name" />
                        </flux:field>

                        <div class="flex items-center gap-4">
                            <flux:button variant="filled" icon="arrow-left" :href="route('cables.index')">Назад</flux:button>
                            <div class="p-3">
                                <flux:button type="submit" variant="primary" color="green" icon="plus">Создать</flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
