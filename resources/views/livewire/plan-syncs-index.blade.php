<?php

use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    #[Computed]
    public function syncs()
    {
        return $this->project->planSyncs()->with('productSets')->latest('id')->get();
    }

    #[Computed]
    public function latestIds()
    {
        return $this->syncs->groupBy('scope')->map(fn ($group) => $group->max('id'))->values();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">3D-план: отправки</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }} · номер проекта для 3D-плана: <b>{{ $project->id }}</b></p>
            </div>
            <div class="flex gap-2">
                <flux:button icon="queue-list" :href="route('projects.cable-journal-versions.index', $project)">Версии журнала</flux:button>
                <flux:button icon="link" :href="route('plan-keys.index')">Ключи → товары</flux:button>
            </div>
        </div>

        @if ($this->syncs->isEmpty())
            <flux:callout>
                <flux:callout.heading>Отправок ещё не было</flux:callout.heading>
                <p>В 3D-плане свяжите квартиру с этим проектом (номер {{ $project->id }}) и нажмите «Отправить в CRM».</p>
            </flux:callout>
        @else
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">№</th>
                            <th class="px-4 py-3">Когда</th>
                            <th class="px-4 py-3">Часть</th>
                            <th class="px-4 py-3">Откуда</th>
                            <th class="px-4 py-3">Журнал</th>
                            <th class="px-4 py-3">Смета</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->syncs as $sync)
                            {{-- Последняя — своя у каждой части: щит не устаревает от отправки трасс. --}}
                            @php($isLatest = $this->latestIds->contains($sync->id))
                            <tr wire:key="sync-{{ $sync->id }}" class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $sync->id }}</td>
                                <td class="px-4 py-3">{{ $sync->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $sync->scopeLabel() }}</td>
                                <td class="px-4 py-3">{{ $sync->source_name ?: $sync->source_project }} · {{ substr($sync->fingerprint, 0, 8) }}</td>
                                <td class="px-4 py-3">
                                    @if (! $sync->hasJournal())
                                        <span class="text-gray-400">—</span>
                                    @elseif ($sync->journal_accepted_at)
                                        <flux:badge size="sm" color="green">принят</flux:badge>
                                    @elseif ($isLatest)
                                        <flux:badge size="sm" color="amber">ждёт</flux:badge>
                                    @else
                                        <flux:badge size="sm">устарел</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @forelse ($sync->productSets as $set)
                                        <flux:badge size="sm" color="green">{{ $set->name }}</flux:badge>
                                    @empty
                                        @if ($isLatest)
                                            <flux:badge size="sm" color="amber">не выдана</flux:badge>
                                        @else
                                            <flux:badge size="sm">—</flux:badge>
                                        @endif
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:button size="xs" color="blue" icon="eye" :href="route('plan-syncs.show', $sync)">Открыть</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
