<?php

use Livewire\Volt\Component;
use App\Models\Project;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // public $projects;

    public function with(): array
    {
        return [
            'projects' => Project::paginate(10),
        ];
    }

    public function delete(Project $project)
    {
        $project->delete();
        session()->flash('message', 'Проект успешно удален!');
    }
}; ?>


<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:button variant="primary" color="green" icon="wallet" :href="route('projects.create')">Добавить проект
        </flux:button>
        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            @if ($projects->hasPages())
            <div class="m-4">
                {{ $projects->links() }}
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
                                Имя проекта
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Клиент
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Адрес
                            </th>
                            <th scope="col" class="px-6 py-3 justify-end flex">
                                Управление
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                        <tr wire:key='{{ $project->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">
                                {{ $project->id }}
                            </td>
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $project->name }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $project->client?->name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ Str::words($project->address, 20) }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('projects.edit', $project)">
                                    Редактировать</flux:button>

                                <flux:button size="xs" color="teal" icon="chart-bar" :href="route('projects.products-summary', $project)">Сводка</flux:button>

                                <flux:button size="xs" color="amber" icon="shopping-bag" :href="route('projects.product-set.index', $project)">Сметы</flux:button>

                                <flux:button size="xs" color="purple" icon="list-bullet" :href="route('projects.purchases.index', $project)">Закупы</flux:button>
                                <flux:button size="xs" color="emerald" icon="document-arrow-up" :href="route('projects.incomes.index', $project)">Доходы</flux:button>

                                <flux:button size="xs" color="rose" icon="document-arrow-down" :href="route('projects.expenses.index', $project)">Расходы</flux:button>

                                <flux:button size="xs" color="cyan" icon="bolt" :href="route('projects.cable-items.index', $project)">Кабели</flux:button>

                                <flux:button size="xs" icon="trash" wire:click="delete({{ $project->id }})"
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