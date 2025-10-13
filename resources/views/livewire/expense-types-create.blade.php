<?php

use App\Models\Expense;
use App\Models\ExpenseType;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public $stay;

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:expense_types,name',
        ]);

        ExpenseType::create([
            'name' => $this->name,
        ]);

        session()->flash('message', 'Тип расходов успешно добавлен!');

        $this->redirect(route('expense-types.index'));
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
                    <div>
                        <flux:input wire:model='name' label="ФИО" />
                    </div>

                    <div class="flex items-center gap-4">
                        <flux:button type="submit" variant="primary" color="green" icon="plus">Создать</flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>