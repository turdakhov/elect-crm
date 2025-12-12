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
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Закупы проекта {{ $project->name }}</flux:heading>
        <div class="flex items-center gap-4">
            <flux:button variant="filled" icon="arrow-left" :href="route('projects.index')">Назад</flux:button>
            <flux:button variant="primary" color="green" icon="plus" :href="route('projects.purchases.create', $project)">Добавить закуп</flux:button>
        </div>

        @if (session()->has('message'))
        <div class="w-full" class="mt-0">
            <flux:callout icon="bell-alert" class="mt-0">
                <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
            </flux:callout>
        </div>
        @endif

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Дата</th>
                            <th class="px-6 py-3">Пользователь</th>
                            <th class="px-6 py-3">Комментарий</th>
                            <th class="px-6 py-3 text-right">Управление</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                        <tr wire:key="purchase-{{ $purchase->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4">{{ $purchase->purchased_at }}</td>
                            <td class="px-6 py-4">{{ $purchase->user?->name }}</td>
                            <td class="px-6 py-4">{{ $purchase->comment }}</td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <flux:button size="xs" color="blue" icon="pencil" :href="route('purchases.edit', $purchase)">Редактировать</flux:button>
                                <flux:button size="xs" color="red" icon="trash" wire:click="delete({{ $purchase->id }})" confirm="Вы уверены, что хотите удалить этот закуп?">Удалить</flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
