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
                <h1 class="text-2xl font-bold">Закупы</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <flux:button variant="primary" color="green" icon="plus" :href="route('projects.purchases.create', $project)">
                Создать закуп
            </flux:button>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        @if ($purchases->count() > 0)
            <div class="space-y-4">
                @foreach ($purchases as $purchase)
                <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Закуп #{{ $purchase->id }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($purchase->purchased_at)->format('d.m.Y') }}
                                @if ($purchase->user)
                                    · {{ $purchase->user->name }}
                                @endif
                            </p>
                            @if ($purchase->comment)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                {{ $purchase->comment }}
                            </p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="primary" icon="pencil" :href="route('purchases.edit', $purchase)">
                                Редактировать
                            </flux:button>
                            <flux:button size="sm" color="blue" icon="shopping-bag" :href="route('purchases.items.index', $purchase)">
                                Позиции ({{ $purchase->items->count() }})
                            </flux:button>
                            <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $purchase->id }})"
                                onclick="return confirm('Вы уверены? Это удалит весь закуп.')">
                                Удалить
                            </flux:button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <flux:callout>
                <flux:callout.heading>Закупы еще не созданы</flux:callout.heading>
                <p>Создайте закуп товаров для этого проекта.</p>
            </flux:callout>
        @endif
    </div>
</div>
