<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\ProjectProductSet;

new class extends Component {
    public Project $project;
    public $name;
    public $comment;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'comment' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Название сметы обязательно.',
            'name.max' => 'Название сметы не должно превышать 255 символов.',
            'comment.max' => 'Комментарий не должен превышать 500 символов.',
        ]);

        ProjectProductSet::create([
            'project_id' => $this->project->id,
            'name' => $this->name,
            'comment' => $this->comment,
        ]);

        session()->flash('message', 'Смета успешно создана!');
        return redirect()->route('projects.product-set.index', $this->project);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <h1 class="text-2xl font-bold">Создать смету</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <form wire:submit="submit" class="space-y-6">
                <flux:field>
                    <flux:label for="name">Название</flux:label>
                    <flux:input id="name" type="text" wire:model="name" placeholder="Введите название сметы"></flux:input>
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label for="comment">Комментарий</flux:label>
                    <flux:textarea id="comment" wire:model="comment" rows="4" placeholder="Введите комментарий (опционально)"></flux:textarea>
                    <flux:error name="comment" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button variant="primary" type="submit">Создать</flux:button>
                    <flux:button variant="ghost" :href="route('projects.product-set.index', $project)">Отмена</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
