<?php

use App\Models\Expense;
use App\Models\Project;
use Livewire\Volt\Component;

new class extends Component {
    public ?Project $project = null;

    public function mount(?Project $project = null): void
    {
        $this->project = $project;
    }

    public function with(): array
    {
        $query = Expense::query()->with('expenseType');
        if ($this->project) {
            $query->where('project_id', $this->project->id);
        }
        return [
            'expenses' => $query->paginate(),
        ];
    }

    public function delete(Expense $expense)
    {
        $expense->delete();
        session()->flash('message', 'Расход успешно удален!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @if ($project)
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Расходы проекта {{ $project->name }}</flux:heading>
        @endif
        <flux:button variant="primary" color="green" icon="document-arrow-down" :href="$project ? route('projects.expenses.create', $project) : route('expenses.create')">Добавить расход
        </flux:button>
        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            @if ($expenses->hasPages())
            <div class="m-4">
                {{ $expenses->links() }}
            </div>
            @endif
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>

                            <th scope="col" class="px-6 py-3">
                                Сумма
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Тип
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Кому
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Дата
                            </th>
                            <th scope="col" class="px-6 py-3 justify-end flex">
                                Управление
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                        <tr wire:key='{{ $expense->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">
                                {{ $expense->id }}
                            </td>

                            <td class="px-6 py-4">
                                {{ number_format($expense->amount, 0, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $expense->expenseType->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $expense->givenTo->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $expense->given_at }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('expenses.edit', $expense)">
                                    Редактировать</flux:button>
                                <flux:button size="xs" icon="trash" wire:click="delete({{ $expense }})"
                                    onclick="return confirm('Are you sure?')">Удалить
                                </flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>