<?php

use App\Models\Income;
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
        $query = Income::query();
        if ($this->project) {
            $query->where('project_id', $this->project->id);
        }

        return [
            'incomes' => $query->paginate(),
        ];
    }

    public function delete(Income $income)
    {
        $income->delete();
        session()->flash('message', 'Доход успешно удален!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @if ($project)
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Доходы проекта {{ $project->name }}</flux:heading>
        @endif
        <flux:button variant="primary" color="green" icon="document-arrow-down" :href="$project ? route('projects.incomes.create', $project) : route('incomes.create')">Добавить доход
        </flux:button>
        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            @if ($incomes->hasPages())
            <div class="m-4">
                {{ $incomes->links() }}
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
                                От кого
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
                        @foreach ($incomes as $income)
                        <tr wire:key='{{ $income->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">
                                {{ $income->id }}
                            </td>

                            <td class="px-6 py-4">
                                {{ number_format($income->amount, 0, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $income->givenBy->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $income->received_at }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('incomes.edit', $income)">
                                    Редактировать</flux:button>
                                <flux:button size="xs" icon="trash" wire:click="delete({{ $income }})"
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