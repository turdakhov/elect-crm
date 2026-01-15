<?php

use App\Models\Cable;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Cable::query()->latest();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return [
            'cables' => $query->paginate(15),
        ];
    }

    public function delete(Cable $cable)
    {
        $cable->delete();
        session()->flash('message', 'Кабель успешно удален!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <flux:button variant="primary" color="green" icon="plus" :href="route('cables.create')">
                Добавить кабель
            </flux:button>
            <div class="flex-1 max-w-md">
                <flux:input wire:model.live="search" placeholder="Поиск кабеля..." icon="magnifying-glass" />
            </div>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            @if ($cables->hasPages())
            <div class="m-4">
                {{ $cables->links() }}
            </div>
            @endif

            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">ID</th>
                            <th scope="col" class="px-6 py-3">Название</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cables as $cable)
                        <tr wire:key='{{ $cable->id }}'
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 align-middle">{{ $cable->id }}</td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-middle">
                                {{ $cable->name }}
                            </th>
                            <td class="px-6 py-4 align-middle">
                                <div class="flex gap-2 justify-end">
                                    <flux:button size="xs" color="blue" icon="pencil" :href="route('cables.edit', $cable)">
                                        Редактировать
                                    </flux:button>
                                    <flux:button size="xs" icon="trash" wire:click="delete({{ $cable }})"
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
