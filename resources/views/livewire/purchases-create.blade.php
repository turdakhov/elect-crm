<?php

use App\Models\Project;
use App\Models\Purchase;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public Project $project;

    public $purchased_at;
    public $user_id;
    public $comment;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->purchased_at = now()->toDateString();
        $this->user_id = auth()->id();
    }

    public function submit(): void
    {
        $this->validate([
            'purchased_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'comment' => 'nullable|string',
        ]);

        Purchase::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user_id,
            'purchased_at' => $this->purchased_at,
            'comment' => $this->comment,
        ]);

        session()->flash('message', 'Закуп создан.');
        $this->redirect(route('projects.purchases.index', $this->project), navigate: true);
    }

    public function with(): array
    {
        return [
            'users' => User::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading level="h3" class="text-2xl md:text-3xl font-semibold">Создать закуп для проекта {{ $project->name }}</flux:heading>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <form wire:submit.prevent="submit" class="space-y-6">
                        <flux:input type="date" wire:model="purchased_at" label="Дата" class="max-w-xs" />
                        <flux:select wire:model="user_id" label="Пользователь">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="comment" label="Комментарий" />
                        <div class="flex items-center gap-4">
                            <div class="p-3">
                                <flux:button type="submit" variant="primary" color="green" icon="plus">Создать</flux:button>
                            </div>
                            @if (session()->has('message'))
                            <div class="w-full" class="mt-0">
                                <flux:callout icon="bell-alert" class="mt-0">
                                    <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                                </flux:callout>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
