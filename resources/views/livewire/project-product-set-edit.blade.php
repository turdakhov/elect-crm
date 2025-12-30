<?php

use Livewire\Volt\Component;
use App\Models\ProjectProductSet;

new class extends Component {
    public ProjectProductSet $projectProductSet;
    public $name;
    public $comment;

    public function mount(ProjectProductSet $projectProductSet)
    {
        $this->projectProductSet = $projectProductSet;
        $this->name = $projectProductSet->name;
        $this->comment = $projectProductSet->comment;
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

        $this->projectProductSet->update([
            'name' => $this->name,
            'comment' => $this->comment,
        ]);

        return redirect()->route('projects.product-set.index', $this->projectProductSet->project)->with('message', 'Смета успешно обновлена!');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <h1 class="text-2xl font-bold">Редактировать смету</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $projectProductSet->project->name }}</p>
        </div>

        @if (session()->has('message'))
        <flux:callout icon="bell-alert">
            <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
        </flux:callout>
        @endif

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
                    <flux:button variant="primary" type="submit">Сохранить</flux:button>
                    <flux:button variant="ghost" :href="route('projects.product-set.index', $projectProductSet->project)">Отмена</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
