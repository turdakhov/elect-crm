<?php

use App\Models\Expense;
use Livewire\Volt\Component;

new class extends Component {
    public $amount;
    public $given_to;
    public $expense_type_id;
    public $given_at;
    public $description;
    public $project_id;
    public $showModal = false;

    public function submit()
    {
        $this->validate([
            'amount' => 'required|numeric',
            'given_to' => 'nullable|exists:users,id',
            'expense_type_id' => 'required|exists:expense_types,id',
            'given_at' => 'required|date',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        Expense::create([
            'amount' => $this->amount,
            'given_to' => $this->given_to,
            'expense_type_id' => $this->expense_type_id,
            'given_at' => $this->given_at,
            'description' => $this->description,
            'project_id' => $this->project_id,
        ]);

        session()->flash('message', 'Расход успешно создан!');

        $this->redirectRoute('expenses.index');
    }

    public function with(): array
    {
        return [
            'expenseTypes' => \App\Models\ExpenseType::all(),
            'users' => \App\Models\User::all(),
            'projects' => \App\Models\Project::all(),
        ];
    }
}; ?>

<div>
    @if ($showModal)
    <div wire:click="hide" class="fixed z-30 w-full h-full inset-0 bg-slate-800 opacity-80"></div>

    <div class="fixed z-40 inset-0 w-1/2 left-1/4 top-1/8">
        <livewire:expense-types-create :stay="true" />
    </div>
    @endif

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div>
                    <form wire:submit.prevent="submit" class="space-y-6 p-6">
                        <x-forms.select name="project_id" cname="name" label="Проект" :collection="$projects" with_empty />
                        <x-forms.select name="expense_type_id" cname="name" label="Тип расходов" :collection="$expenseTypes" with_empty />
                        <x-forms.select name="given_to" cname="name" label="Кому" :collection="$users" with_empty />
                        <flux:input wire:model='amount' label="Сумма" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:input wire:model='given_at' label="Дата выплаты" type="date" />
                        <flux:button variant="primary" color="green" type="submit" icon="plus">Создать</flux:button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>