<?php

use App\Models\Income;
use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public $project_id;
    public $amount;
    public $description;
    public $given_by;
    public $received_at;
    public $contract_number;

    public function with()
    {
        /** @disregard P1005, P1006 */
        return [
            'projects' => Project::all(),
            'users' => User::clients()->orWhere(function ($query) {
                return $query->supervisors();
            })->orWhere(function ($query) {
                return $query->foremen();
            })->get(),
        ];
    }

    public function submit()
    {
        $this->validate([
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:1',
            'given_by' => 'nullable|exists:users,id',
            'contract_number' => 'nullable',
            'description' => 'required',
            'received_at' => 'required',
        ]);

        Income::create([
            'project_id' => $this->project_id,
            'amount' => $this->amount,
            'given_by' => $this->given_by,
            'contract_number' => $this->contract_number,
            'description' => $this->description,
            'received_at' => $this->received_at,
        ]);

        session()->flash('success', 'Доход успешно добавлен!');

        $this->redirectRoute('incomes.index');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl bg-white">

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">

                    <x-forms.select name="project_id" cname="name" label="Проект" :collection="$projects" with_empty />
                    <x-forms.select name="given_by" cname="name" label="От кого" :collection="$users" with_empty />
                    <flux:input wire:model='amount' label="Сумма" />
                    <flux:input wire:model='contract_number' label="Номер договора" />
                    <flux:textarea wire:model='description' label="Описание" />
                    <flux:input wire:model='received_at' label="Дата выплаты" type="date" />
                    <flux:button wire:click="submit" variant="primary" color="green" type="button" icon="plus" command="close" commandfor="dialog">Создать</flux:button>

                </div>
            </div>
        </div>
    </div>
</div>