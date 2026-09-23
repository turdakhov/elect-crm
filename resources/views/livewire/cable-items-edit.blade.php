<?php

use App\Models\{CableItem, Cable, Pipe};
use Livewire\Volt\Component;

new class extends Component {
    public CableItem $cableItem;
    public $floor;
    public $room;
    public $name;
    public $comment;
    public $cable_id;
    public $cable_count;
    public $pipe_id;
    public $cable_length;
    public $pipe_length;

    public function mount(CableItem $cableItem)
    {
        $this->cableItem = $cableItem;
        $this->floor = $cableItem->floor;
        $this->room = $cableItem->room;
        $this->name = $cableItem->name;
        $this->comment = $cableItem->comment;
        $this->cable_id = $cableItem->cable_id;
        $this->cable_count = $cableItem->cable_count;
        $this->pipe_id = $cableItem->pipe_id;
        $this->cable_length = $cableItem->cable_length;
        $this->pipe_length = $cableItem->pipe_length;
    }

    public function with(): array
    {
        return [
            'cables' => Cable::all(),
            'pipes' => Pipe::all(),
        ];
    }

    public function submit()
    {
        $this->validate([
            'room' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'cable_id' => 'required|exists:cables,id',
            'cable_count' => 'required|integer|min:1|max:1000',
            'pipe_id' => 'required|exists:pipes,id',
            'cable_length' => 'required|numeric|min:0',
            'pipe_length' => 'required|numeric|min:0',
        ]);

        $this->cableItem->update([
            'floor' => $this->floor,
            'room' => $this->room,
            'name' => $this->name,
            'comment' => $this->comment,
            'cable_id' => $this->cable_id,
            'cable_count' => $this->cable_count,
            'pipe_id' => $this->pipe_id,
            'cable_length' => $this->cable_length,
            'pipe_length' => $this->pipe_length,
        ]);

        session()->flash('message', 'Позиция обновлена!');
        return redirect()->route('projects.cable-items.index', $this->cableItem->project);
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <div>
                        <h1 class="text-2xl font-bold">Редактировать позицию</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ $cableItem->project->name }}</p>
                    </div>

                    <form wire:submit="submit" class="space-y-6">
                        <flux:field>
                            <flux:label>Этаж</flux:label>
                            <flux:input wire:model="floor"></flux:input>
                            <flux:error name="floor" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Комната *</flux:label>
                            <flux:input wire:model="room"></flux:input>
                            <flux:error name="room" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Кабель *</flux:label>
                            <flux:select wire:model="cable_id">
                                @foreach($cables as $cable)
                                    <option value="{{ $cable->id }}">{{ $cable->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="cable_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Гофра *</flux:label>
                            <flux:select wire:model="pipe_id">
                                @foreach($pipes as $pipe)
                                    <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="pipe_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Название *</flux:label>
                            <flux:input wire:model="name"></flux:input>
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Длина кабеля (м) *</flux:label>
                            <flux:input type="text" inputmode="decimal" wire:model="cable_length"></flux:input>
                            <flux:error name="cable_length" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Кол-во кабелей в гофре *</flux:label>
                            <flux:input type="text" inputmode="numeric" wire:model="cable_count"></flux:input>
                            <flux:error name="cable_count" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Длина гофры (м) *</flux:label>
                            <flux:input type="text" inputmode="decimal" wire:model="pipe_length"></flux:input>
                            <flux:error name="pipe_length" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Комментарий</flux:label>
                            <flux:textarea wire:model="comment" rows="3"></flux:textarea>
                            <flux:error name="comment" />
                        </flux:field>

                        <div class="flex items-center gap-4">
                            <flux:button variant="filled" icon="arrow-left" :href="route('projects.cable-items.index', $cableItem->project)">Назад</flux:button>
                            <flux:button type="submit" variant="primary" color="green" icon="pencil">Сохранить</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
