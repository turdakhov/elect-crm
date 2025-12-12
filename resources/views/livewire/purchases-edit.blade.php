<?php

use App\Models\Purchase;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public Purchase $purchase;

    public $purchased_at;
    public $user_id;
    public $comment;

    public function mount(Purchase $purchase)
    {
        $this->purchase = $purchase;
        $this->purchased_at = $purchase->purchased_at;
        $this->user_id = $purchase->user_id;
        $this->comment = $purchase->comment;
    }

    public function submit(): void
    {
        $this->validate([
            'purchased_at' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'comment' => 'nullable|string',
        ]);

        $this->purchase->update([
            'purchased_at' => $this->purchased_at,
            'user_id' => $this->user_id,
            'comment' => $this->comment,
        ]);

        session()->flash('message', 'Закуп обновлен.');
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
                            <flux:button variant="filled" icon="arrow-left" :href="route('projects.purchases.index', $purchase->project)">Назад</flux:button>
                            <div class="p-3">
                                <flux:button type="submit" variant="primary" color="green" icon="pencil">Обновить</flux:button>
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
