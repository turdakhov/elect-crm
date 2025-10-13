<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public $phone;
    public $description;
    public User $user;

    public function mount(User $user)
    {
        $this->name = $user->name;
        $this->phone = $user->phone;
        $this->description = $user->description;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $this->user->id,
            'phone' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->user->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'description' => $this->description,
        ]);

        session()->flash('message', 'Пользователь успешно изменен!');
    }
    //
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="relative overflow-x-auto">
                <div>
                    <form wire:submit.prevent="update" class="space-y-6 p-6">
                        <flux:input wire:model='name' label="ФИО" />
                        <flux:input wire:model='phone' label="Телефон" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <div class="flex items-center gap-4">
                            <div class="p-3">
                                <flux:button variant="primary" color="green" type="submit" icon="plus">Сохранить изменения</flux:button>
                            </div>
                            @if (session()->has('message'))
                            <div class="w-full">
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