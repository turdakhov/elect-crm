<?php

use App\Models\Expense;
use Livewire\Volt\Component;

new class extends Component {
    public $project_id;
    public $amount;
    public $given_to;
    public $expense_type_id;
    public $given_at;
    public $description;
    public Expense $expense;

    public function mount(Expense $expense)
    {
        $this->expense = $expense;
        $this->project_id = $expense->project_id;
        $this->amount = $expense->amount;
        $this->given_to = $expense->given_to;
        $this->expense_type_id = $expense->expense_type_id;
        $this->given_at = $expense->given_at;
        $this->description = $expense->description;
    }

    public function update()
    {
        $this->validate([
            'amount' => 'required|numeric',
            'given_to' => 'nullable|exists:users,id',
            'expense_type_id' => 'required|exists:expense_types,id',
            'given_at' => 'required|date',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $this->expense->update([
            'project_id' => $this->project_id,
            'amount' => $this->amount,
            'given_to' => $this->given_to,
            'expense_type_id' => $this->expense_type_id,
            'given_at' => $this->given_at,
        ]);

        session()->flash('message', 'Расход успешно обновлен.');
        
        if ($this->expense->project_id) {
            return redirect()->route('projects.expenses.index', $this->expense->project_id);
        }
        return redirect()->route('expenses.index');
    }

    public function with()
    {
        return [
            'projects' => \App\Models\Project::all(),
            'expenseTypes' => \App\Models\ExpenseType::all(),
            'users' => \App\Models\User::all(),
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <div>
                        <h1 class="text-2xl font-bold">Редактировать расход</h1>
                    </div>

                    <form wire:submit.prevent="update" class="space-y-6 p-6">
                        <x-forms.select name="project_id" cname="name" label="Проект" :collection="$projects" with_empty />
                        <x-forms.select name="expense_type_id" cname="name" label="Тип расходов" :collection="$expenseTypes" with_empty />
                        <x-forms.select name="given_to" cname="name" label="Кому" :collection="$users" with_empty />
                        <flux:input wire:model='amount' label="Сумма" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:input wire:model='given_at' label="Дата выплаты" type="date" class="max-w-xs" />
                        <div class="flex items-center gap-4">
                            @if ($expense->project_id)
                            <flux:button variant="filled" icon="arrow-left" :href="route('projects.expenses.index', $expense->project_id)">Назад</flux:button>
                            @else
                            <flux:button variant="filled" icon="arrow-left" :href="route('expenses.index')">Назад</flux:button>
                            @endif
                            <div class="p-3">
                                <flux:button variant="primary" color="green" type="submit" icon="plus">Сохранить изменения</flux:button>
                            </div>
                        </div>
                        @if (session()->has('message'))
                        <flux:callout icon="bell-alert">
                            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                        </flux:callout>
                        @endif
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>