<?php

use Livewire\Volt\Component;
use App\Models\Project;
use Livewire\Attributes\Computed;

new class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    #[Computed]
    public function productSets()
    {
        return $this->project->productSets()->orderBy('created_at', 'desc')->get();
    }

    public function deleteSet($id)
    {
        $set = $this->project->productSets()->find($id);
        if ($set) {
            $this->authorize('delete', $set);
            $set->delete();
            session()->flash('message', 'Смета успешно удалена!');
        }
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Сметы товаров</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <flux:button variant="primary" color="green" icon="plus" :href="route('projects.product-set.create', $project)">
                Создать смету
            </flux:button>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

        @if ($this->productSets->count() > 0)
            <div class="space-y-4">
                @foreach ($this->productSets as $set)
                <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                    <h2 class="text-lg font-semibold mb-2">{{ $set->name }}</h2>
                    @if ($set->comment)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $set->comment }}</p>
                    @endif

                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="primary" icon="pencil" :href="route('project-product-set.edit', $set)">
                            Редактировать
                        </flux:button>
                        <flux:button size="sm" color="blue" icon="shopping-bag" :href="route('project-product-set.items.index', $set)">
                            Товары ({{ $set->items->count() }})
                        </flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteSet({{ $set->id }})"
                            onclick="return confirm('Вы уверены? Это удалит всю смету.')">
                            Удалить
                        </flux:button>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <flux:callout>
                <flux:callout.heading>Сметы еще не созданы</flux:callout.heading>
                <p>Создайте смету товаров для этого проекта.</p>
            </flux:callout>
        @endif
    </div>
</div>
