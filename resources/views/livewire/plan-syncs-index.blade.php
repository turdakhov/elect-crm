<?php

use App\Models\PlanSync;
use App\Models\Project;
use App\Services\Plan3d\EstimateSync;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public Project $project;

    /** Отмеченные к выдаче одной сметой. */
    public array $selected = [];

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    #[Computed]
    public function syncs()
    {
        return $this->project->planSyncs()->with('productSet')->latest('id')->get();
    }

    /** Последняя — своя у каждой части: щит не устаревает от отправки трасс. */
    #[Computed]
    public function latestIds()
    {
        return $this->syncs->groupBy('scope')->map(fn ($group) => $group->max('id'))->values();
    }

    /** id отправок, которые можно выдать сейчас, и сколько строк каждая даст. */
    #[Computed]
    public function issuable(): array
    {
        return EstimateSync::issuable($this->project)
            ->mapWithKeys(fn (PlanSync $s) => [$s->id => count(EstimateSync::billable(EstimateSync::delta($s)))])
            ->all();
    }

    public function selectPanels(): void
    {
        $this->selected = $this->syncs->whereIn('id', array_keys($this->issuable))
            ->filter(fn (PlanSync $s) => ! $s->hasJournal())->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
    }

    public function issueSelected()
    {
        $syncs = PlanSync::where('project_id', $this->project->id)->whereIn('id', $this->selected)->orderBy('id')->get();
        try {
            $set = EstimateSync::issueMany($syncs, auth()->user());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            unset($this->syncs, $this->issuable);

            return null;
        }
        session()->flash('message', '«'.$set->name.'» создана.');

        return $this->redirectRoute('project-product-set.items.index', $set, navigate: true);
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

        @if (session()->has('error'))
            <flux:callout icon="exclamation-triangle" color="red"><flux:callout.heading>{{ session('error') }}</flux:callout.heading></flux:callout>
        @endif

        @if ($this->syncs->isEmpty())
            <flux:callout>
                <flux:callout.heading>Отправок ещё не было</flux:callout.heading>
                <p>В 3D-плане свяжите квартиру с этим проектом (номер {{ $project->id }}) и нажмите «Отправить щиты» или «Отправить всё».</p>
            </flux:callout>
        @else
            @if ($this->issuable)
                <div class="flex flex-wrap items-center gap-3 rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Отметьте отправки — их разница выдастся одной сметой, одинаковые строки сложатся.</span>
                    <flux:button size="sm" wire:click="selectPanels">Все щиты</flux:button>
                    <flux:button size="sm" variant="primary" color="green" icon="document-check" wire:click="issueSelected"
                        :disabled="! $selected" onclick="return confirm('Создать одну смету из выбранных отправок?')">
                        Создать одну смету из выбранных ({{ count($selected) }})
                    </flux:button>
                </div>
            @endif

            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3">№</th>
                            <th class="px-4 py-3">Когда</th>
                            <th class="px-4 py-3">Часть</th>
                            <th class="px-4 py-3">Журнал</th>
                            <th class="px-4 py-3">Смета</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->syncs as $sync)
                            @php($isLatest = $this->latestIds->contains($sync->id))
                            <tr wire:key="sync-{{ $sync->id }}" class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-3">
                                    @if (isset($this->issuable[$sync->id]))
                                        <input type="checkbox" value="{{ $sync->id }}" wire:model.live="selected" class="h-4 w-4">
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $sync->id }}</td>
                                <td class="px-4 py-3">{{ $sync->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $sync->scopeLabel() }}</td>
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
                                    @if ($sync->productSet)
                                        <flux:badge size="sm" color="green">{{ $sync->productSet->name }}</flux:badge>
                                    @elseif (isset($this->issuable[$sync->id]))
                                        <flux:badge size="sm" color="amber">к выдаче: {{ $this->issuable[$sync->id] }} строк</flux:badge>
                                    @elseif ($isLatest)
                                        <flux:badge size="sm">разницы нет</flux:badge>
                                    @else
                                        <flux:badge size="sm">—</flux:badge>
                                    @endif
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
