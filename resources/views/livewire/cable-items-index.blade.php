<?php

use App\Models\CableItem;
use App\Models\Project;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Project $project;
    public string $search = '';
    public string $floorFilter = '';
    public string $exportFloor = '';
    public string $deleteFloor = '';

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFloorFilter()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = CableItem::query()
            ->with(['cable', 'pipe'])
            ->where('project_id', $this->project->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%')
                  ->orWhere('room', 'like', '%' . $this->search . '%')
                  ->orWhere('floor', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->floorFilter) {
            $query->where('floor', $this->floorFilter);
        }

        $query->orderBy('floor')
              ->orderBy('room')
              ->orderBy('cable_id');

        $floors = CableItem::where('project_id', $this->project->id)
            ->distinct()
            ->orderBy('floor')
            ->pluck('floor')
            ->filter()
            ->values();

        return [
            'cableItems' => $query->paginate(15),
            'floors' => $floors,
            'deleteCount' => CableItem::where('project_id', $this->project->id)
                ->when($this->deleteFloor !== '', fn ($query) => $query->where('floor', $this->deleteFloor))
                ->count(),
        ];
    }

    public function delete(CableItem $cableItem)
    {
        $cableItem->delete();
        session()->flash('message', 'Позиция удалена!');
    }

    public function deleteCables(): void
    {
        $floor = $this->deleteFloor;

        $deletedCount = CableItem::where('project_id', $this->project->id)
            ->when($floor !== '', fn ($query) => $query->where('floor', $floor))
            ->delete();

        if ($floor === '' || $this->floorFilter === $floor) {
            $this->floorFilter = '';
        }

        if ($floor === '' || $this->exportFloor === $floor) {
            $this->exportFloor = '';
        }

        $this->deleteFloor = '';
        $this->resetPage();

        $scope = $floor === '' ? 'во всех этажах' : "на этаже «{$floor}»";
        session()->flash('message', "Удалено позиций {$scope}: {$deletedCount}.");
    }

}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Кабели и гофры</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" color="blue" icon="arrow-up-tray" :href="route('projects.cable-items.import', $project)">
                    Импорт CSV
                </flux:button>
                <div class="min-w-[180px]">
                    <flux:select wire:model.live="exportFloor" aria-label="Этаж для экспорта">
                        <option value="">Все этажи</option>
                        @foreach($floors as $floor)
                            <option value="{{ $floor }}">{{ $floor }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button variant="primary" color="blue" icon="arrow-down-tray" :href="route('projects.cable-items.export-pdf', ['project' => $project, 'floor' => $exportFloor ?: null])" target="_blank">
                    Экспорт PDF
                </flux:button>
                <flux:button variant="primary" color="blue" icon="chart-bar" :href="route('projects.cable-items.summary', $project)">
                    Подсчет
                </flux:button>
                @if ($floors->isNotEmpty() || $deleteCount > 0)
                <div class="min-w-[180px]">
                    <flux:select wire:model.live="deleteFloor" aria-label="Этаж для удаления">
                        <option value="">Все этажи</option>
                        @foreach($floors as $floor)
                            <option value="{{ $floor }}">{{ $floor }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button variant="danger" icon="trash" wire:click="deleteCables"
                    wire:confirm="{{ $deleteFloor === '' ? 'Удалить ВСЕ кабели проекта «'.$project->name.'»' : 'Удалить кабели этажа «'.$deleteFloor.'»' }} ({{ $deleteCount }} шт.)? Отменить будет нельзя.">
                    {{ $deleteFloor === '' ? 'Удалить все' : 'Удалить этаж' }}
                </flux:button>
                @endif
            </div>
        </div>

        <div class="flex gap-4 items-end">
            <div class="flex-1 max-w-md">
                <flux:input wire:model.live="search" placeholder="Поиск..." icon="magnifying-glass" />
            </div>
            <div class="w-48">
                <label for="floorFilter" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Этаж</label>
                <select 
                    id="floorFilter"
                    wire:model.live="floorFilter" 
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="">Все этажи</option>
                    @foreach($floors as $floor)
                        <option value="{{ $floor }}">{{ $floor }}</option>
                    @endforeach
                </select>
            </div>
            <flux:button variant="primary" color="green" icon="plus" :href="route('projects.cable-items.create', $project)" class="ml-auto">
                Добавить позицию
            </flux:button>
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
                                @if ($item->code)
                                <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $item->code }}</span>
                                @endif
                                @if ($item->comment)
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-normal mt-1">
                                    {{ Str::limit($item->comment, 50) }}
                                </div>
                                @endif
                            </th>
                            <td class="px-6 py-4 align-middle">{{ $item->cable->name }}</td>
                            <td class="px-6 py-4 align-middle whitespace-nowrap">
                                {{ number_format($item->cable_length, 2) }} м
                                @if ($item->cable_count > 1)
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">×{{ $item->cable_count }}</span>
                                @endif
                            </td>
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
            @if ($cableItems->hasPages())
            <div class="m-4">
                {{ $cableItems->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
