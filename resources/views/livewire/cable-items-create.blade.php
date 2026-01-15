<?php

use App\Models\{Cable, CableItem, Pipe, Project};
use Livewire\Volt\Component;

new class extends Component {
    public $project_id;
    public $floor = '';
    public $room = '';
    public $name = '';
    public $comment = '';
    public $cable_id;
    public $pipe_id;
    public $cable_length = 0;
    public $pipe_length = 0;

    public function mount(?Project $project = null): void
    {
        if ($project) {
            $this->project_id = $project->id;
        }
    }

    public function with(): array
    {
        return [
            'cables' => Cable::all(),
            'pipes' => Pipe::all(),
            'project' => Project::findOrFail($this->project_id),
        ];
    }

    public function save(): void
    {
        $this->validate([
            'project_id' => 'required|exists:projects,id',
            'floor' => 'required|string',
            'room' => 'required|string',
            'name' => 'required|string',
            'comment' => 'nullable|string',
            'cable_id' => 'nullable|exists:cables,id',
            'pipe_id' => 'nullable|exists:pipes,id',
            'cable_length' => 'nullable|numeric|min:0',
            'pipe_length' => 'nullable|numeric|min:0',
        ]);

        CableItem::create([
            'project_id' => $this->project_id,
            'floor' => $this->floor,
            'room' => $this->room,
            'name' => $this->name,
            'comment' => $this->comment,
            'cable_id' => $this->cable_id,
            'pipe_id' => $this->pipe_id,
            'cable_length' => $this->cable_length,
            'pipe_length' => $this->pipe_length,
        ]);

        // Очищаем только наименование, комментарий и длины
        // Сохраняем этаж, комнату, кабель и гофру для следующей записи
        $this->name = '';
        $this->comment = '';
        $this->cable_length = 0;
        $this->pipe_length = 0;

        session()->flash('message', 'Позиция успешно добавлена!');
        
        $this->dispatch('item-saved');
    }

    public function submit(): \Illuminate\Http\RedirectResponse
    {
        $this->save();

        return redirect()->route('projects.cable-items.index', ['project' => $this->project_id]);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div>
                    <div class="p-6">
                        <div class="mb-4">
                            <flux:button variant="filled" icon="arrow-left" :href="route('projects.cable-items.index', $project->id)">Назад</flux:button>
                        </div>
                        
                        <h1 class="mb-1 text-2xl font-bold">Добавить кабель/гофру</h1>
                        <p class="mb-6 text-gray-600 dark:text-gray-400">{{ $project->name }}</p>

                        <form wire:submit="save" class="space-y-6" x-data="{ focusNameField() { setTimeout(() => { const input = document.querySelector('input[wire\\:model=name]'); if(input) input.focus(); }, 100); } }" x-init="$wire.on('item-saved', () => focusNameField())">
                            <flux:input wire:model='floor' label="Этаж" />
                            <flux:input wire:model='room' label="Помещение" />
                            <flux:textarea wire:model='comment' label="Комментарий" />
                            <x-forms.select name="cable_id" cname="name" label="Кабель" :collection="$cables" with_empty />
                            <x-forms.select name="pipe_id" cname="name" label="Гофра" :collection="$pipes" with_empty />
                            <flux:input wire:model='name' label="Наименование" autofocus=""/>
                            <flux:input wire:model='cable_length' label="Длина кабеля (м)" type="number" step="0.01" />
                            <flux:input wire:model='pipe_length' label="Длина гофры (м)" type="number" step="0.01" />

                            <div class="flex gap-2">
                                <flux:button variant="primary" type="submit" icon="plus">Создать и еще один</flux:button>
                                <flux:button variant="primary" wire:click="submit" icon="check">Создать</flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
