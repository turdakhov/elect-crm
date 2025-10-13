<?php

use App\Models\Expense;
use App\Models\ExpenseType;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public ExpenseType $expenseType;

    public function mount(ExpenseType $expenseType)
    {
        $this->expenseType = $expenseType;
        $this->name = $expenseType->name;
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:expense_types,name,' . $this->expenseType->id,
        ]);

        $this->expenseType->update([
            'name' => $this->name,
        ]);

        session()->flash('message', 'Тип расходов успешно изменен!');
    }
}; ?>

<div>
    <div>
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

                <div class="relative overflow-x-auto">
                    <div>
                        <form wire:submit.prevent="submit" class="space-y-6 p-6">
                            <flux:input wire:model='name' label="Наименование" />

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
</div>