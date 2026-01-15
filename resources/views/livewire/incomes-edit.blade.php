<?php

use App\Models\Income;
use Livewire\Volt\Component;

new class extends Component {
    public $income;
    public $amount;
    public $contract_number;
    public $description;
    public $received_at;

    public function mount(Income $income)
    {
        $this->income = $income;
        $this->amount = $income->amount;
        $this->contract_number = $income->contract_number;
        $this->description = $income->description;
        $this->received_at = $income->received_at;
    }

    public function submit()
    {
        $this->validate([
            'amount' => 'required|numeric',
            'contract_number' => 'nullable|string|max:255',
            'description' => 'required|string',
            'received_at' => 'required|date',
        ]);

        $this->income->update([
            'amount' => $this->amount,
            'contract_number' => $this->contract_number,
            'description' => $this->description,
            'received_at' => $this->received_at,
        ]);

        session()->flash('message', 'Доход успешно обновлен.');
        
        if ($this->income->project_id) {
            return redirect()->route('projects.incomes.index', $this->income->project_id);
        }
        return redirect()->route('incomes.index');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <div>
                        <h1 class="text-2xl font-bold">Редактировать доход</h1>
                    </div>

                    <form wire:submit.prevent="submit" class="space-y-6 p-6">
                        <flux:input wire:model='amount' label="Сумма" />
                        <flux:input wire:model='contract_number' label="Номер договора" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:input wire:model='received_at' label="Дата выплаты" type="date" class="max-w-xs" />
                        <div class="flex items-center gap-4">
                            @if ($income->project_id)
                            <flux:button variant="filled" icon="arrow-left" :href="route('projects.incomes.index', $income->project_id)">Назад</flux:button>
                            @else
                            <flux:button variant="filled" icon="arrow-left" :href="route('incomes.index')">Назад</flux:button>
                            @endif
                            <div class="p-3">
                                <flux:button type="submit" variant="primary" color="green" icon="plus">Обновить</flux:button>
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