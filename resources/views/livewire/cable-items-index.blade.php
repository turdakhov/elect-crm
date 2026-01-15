<?php

use App\Models\CableItem;
use App\Models\Project;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Project $project;
    public string $search = '';

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = CableItem::query()
            ->with(['cable', 'pipe'])
            ->where('project_id', $this->project->id)
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('room', 'like', '%' . $this->search . '%')
                  ->orWhere('floor', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'cableItems' => $query->paginate(15),
        ];
    }

    public function delete(CableItem $cableItem)
    {
        $cableItem->delete();
        session()->flash('message', 'Позиция удалена!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Кабели и гофры</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" color="green" icon="plus" :href="route('projects.cable-items.create', $project)">
                    Добавить позицию
                </flux:button>
            </div>
        </div>

        <div class="flex-1 max-w-md">
            <flux:input wire:model.live="search" placeholder="Поиск..." icon="magnifying-glass" />
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            @if ($cableItems->hasPages())
            <div class="m-4">
                {{ $cableItems->links() }}
            </div>
            @endif

            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Этаж</th>
                            <th scope="col" class="px-6 py-3">Комната</th>
                            <th scope="col" class="px-6 py-3">Название</th>
                            <th scope="col" class="px-6 py-3">Кабель</th>
                            <th scope="col" class="px-6 py-3">Длина кабеля</th>
                            <th scope="col" class="px-6 py-3">Гофра</th>
                            <th scope="col" class="px-6 py-3">Длина гофры</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cableItems as $item)
                        <tr wire:key='{{ $item->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 align-middle">{{ $item->floor ?? '—' }}</td>
                            <td class="px-6 py-4 align-middle">{{ $item->room }}</td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white align-middle">
                                {{ $item->name }}
                                @if ($item->comment)
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal mt-1">
                                    {{ Str::limit($item->comment, 50) }}
                                </div>
                                @endif
                            </th>
                            <td class="px-6 py-4 align-middle">{{ $item->cable->name }}</td>
                            <td class="px-6 py-4 align-middle">{{ number_format($item->cable_length, 2) }} м</td>
                            <td class="px-6 py-4 align-middle">{{ $item->pipe->name }}</td>
                            <td class="px-6 py-4 align-middle">{{ number_format($item->pipe_length, 2) }} м</td>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex gap-2 justify-end">
                                    <flux:button size="xs" color="blue" icon="pencil" :href="route('cable-items.edit', $item)">
                                        Редактировать
                                    </flux:button>
                                    <flux:button size="xs" icon="trash" wire:click="delete({{ $item }})"
                                        onclick="return confirm('Вы уверены?')">
                                        Удалить
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
