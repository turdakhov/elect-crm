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

}; ?>


<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:button variant="primary" color="green" icon="home" :href="route('complexes.create')">Добавить проект
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
                                Название ЖК
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Адрес
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Описание
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
                                {{ $project->address }}
                            </td>
                            <td class="px-6 py-4">
                                {{ Str::words($project->description, 10) }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('projects.edit', $project)">
                                    Редактировать</flux:button>
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

