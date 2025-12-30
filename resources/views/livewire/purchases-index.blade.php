<?php

use App\Models\Project;
use App\Models\Purchase;
use Livewire\Volt\Component;

new class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function with(): array
    {
        return [
            'purchases' => Purchase::where('project_id', $this->project->id)->latest()->get(),
        ];
    }

    public function delete(Purchase $purchase): void
    {
        $purchase->delete();
        session()->flash('message', 'Закуп удален.');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Закупы проекта</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button variant="primary" icon="plus" :href="route('projects.purchases.create', $project)">
                    Добавить закуп
                </flux:button>
                <flux:button variant="ghost" icon="arrow-left" :href="route('projects.index')">
                    Назад
                </flux:button>
            </div>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        @if ($purchases->count() > 0)
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Дата</th>
                            <th scope="col" class="px-6 py-3">Пользователь</th>
                            <th scope="col" class="px-6 py-3">Комментарий</th>
                            <th scope="col" class="px-6 py-3 text-center">Позиции</th>
                            <th scope="col" class="px-6 py-3 justify-end flex">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                        <tr wire:key="purchase-{{ $purchase->id }}"
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($purchase->purchased_at)->format('d.m.Y') }}</td>
                            <td class="px-6 py-4">{{ $purchase->user?->name }}</td>
                            <td class="px-6 py-4">{{ Str::limit($purchase->comment, 50) }}</td>
                            <td class="px-6 py-4 text-center">
                                <flux:button size="xs" color="purple" icon="list-bullet" :href="route('purchases.items.index', $purchase)">
                                    Позиции
                                </flux:button>
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-end">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('purchases.edit', $purchase)">
                                    Редактировать
                                </flux:button>
                                <flux:button size="xs" color="rose" icon="trash" wire:click="delete({{ $purchase->id }})"
                                    onclick="return confirm('Удалить закуп?')">
                                    Удалить
                                </flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <flux:callout>
            <flux:callout.heading>Закупов нет</flux:callout.heading>
            <p>В проекте еще нет закупов. Добавьте закуп, чтобы начать.</p>
        </flux:callout>
        @endif

        <div class="flex gap-3">
            <flux:button variant="ghost" :href="route('projects.index')">Назад к проектам</flux:button>
        </div>
    </div>
</div>
