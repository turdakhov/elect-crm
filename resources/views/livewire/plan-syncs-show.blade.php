<?php

use App\Models\PlanSync;
use App\Services\Plan3d\EstimateSync;
use App\Services\Plan3d\JournalSync;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public PlanSync $planSync;

    public function mount(PlanSync $planSync)
    {
        $this->planSync = $planSync;
    }

    #[Computed]
    public function latest(): bool
    {
        return $this->planSync->isLatest();
    }

    #[Computed]
    public function journalDiff(): array
    {
        if (! $this->planSync->hasJournal()) {
            return ['added' => [], 'removed' => [], 'changed' => [], 'unchanged' => 0];
        }

        return JournalSync::diffFor($this->planSync);
    }

    #[Computed]
    public function journalErrors(): array
    {
        return JournalSync::errors($this->planSync->journal);
    }

    #[Computed]
    public function newCatalogNames(): array
    {
        return JournalSync::newCatalogNames($this->planSync);
    }

    #[Computed]
    public function lines(): array
    {
        return EstimateSync::delta($this->planSync);
    }

    #[Computed]
    public function blockers(): array
    {
        return EstimateSync::blockers($this->planSync, $this->lines);
    }

    public function acceptJournal(): void
    {
        try {
            $version = JournalSync::accept($this->planSync, auth()->user());
            session()->flash('message', 'Журнал принят: версия №'.$version->number.'.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->planSync->refresh();
        unset($this->journalDiff, $this->latest);
    }

    public function issueEstimate()
    {
        try {
            $set = EstimateSync::issue($this->planSync, auth()->user());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            unset($this->lines, $this->blockers);

            return null;
        }
        session()->flash('message', '«'.$set->name.'» выдана.');

        return $this->redirectRoute('project-product-set.items.index', $set, navigate: true);
    }

    public function qty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Отправка №{{ $planSync->id }} из 3D-плана · {{ $planSync->scopeLabel() }}</h1>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ $planSync->project->name }} · {{ $planSync->created_at->format('d.m.Y H:i') }} ·
                    {{ $planSync->source_name ?: $planSync->source_project }} · модель {{ substr($planSync->fingerprint, 0, 8) }}
                </p>
            </div>
            <flux:button variant="ghost" :href="route('projects.plan-syncs.index', $planSync->project)">Все отправки</flux:button>
        </div>

        @if (session()->has('message'))
            <flux:callout icon="check-circle" color="green">
                <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
            </flux:callout>
        @endif
        @if (session()->has('error'))
            <flux:callout icon="exclamation-triangle" color="red">
                <flux:callout.heading>{{ session('error') }}</flux:callout.heading>
            </flux:callout>
        @endif

        @unless ($this->latest)
            <flux:callout icon="clock" color="amber">
                <flux:callout.heading>Есть отправка новее</flux:callout.heading>
                <p>По части «{{ $planSync->scopeLabel() }}» пришла отправка новее. Эта — только для просмотра: принимать журнал и выдавать смету надо по последней.</p>
            </flux:callout>
        @endunless

        @if ($planSync->warnings)
            <flux:callout icon="exclamation-triangle" color="amber">
                <flux:callout.heading>3D-план предупреждал при отправке</flux:callout.heading>
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($planSync->warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        {{-- Журнал — только у трасс --}}
        @if ($planSync->hasJournal())
        <section class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            @php($diff = $this->journalDiff)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Кабельный журнал</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Строк {{ count($planSync->journal) }}: новых {{ count($diff['added']) }}, изменённых {{ count($diff['changed']) }},
                        пропавших {{ count($diff['removed']) }}, без изменений {{ $diff['unchanged'] }}.
                    </p>
                </div>
                @if ($planSync->journal_accepted_at)
                    <flux:badge color="green">Принят {{ $planSync->journal_accepted_at->format('d.m.Y H:i') }}{{ $planSync->journalAcceptedBy ? ', '.$planSync->journalAcceptedBy->name : '' }}</flux:badge>
                @elseif ($this->latest && ! $this->journalErrors)
                    <flux:button variant="primary" icon="check" wire:click="acceptJournal"
                        onclick="return confirm('Журнал проекта будет заменён этим целиком. Принять?')">
                        Принять журнал
                    </flux:button>
                @endif
            </div>

            @if ($this->journalErrors)
                <flux:callout icon="x-circle" color="red" class="mb-4">
                    <flux:callout.heading>Журнал принять нельзя — поправьте в 3D-плане и отправьте заново</flux:callout.heading>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach (array_slice($this->journalErrors, 0, 30) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:callout>
            @endif

            @if (! $planSync->journal_accepted_at && ($this->newCatalogNames['cable'] || $this->newCatalogNames['pipe']))
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Принятие заведёт в справочники:
                    @if ($this->newCatalogNames['cable']) кабели — {{ implode(', ', $this->newCatalogNames['cable']) }}. @endif
                    @if ($this->newCatalogNames['pipe']) гофры — {{ implode(', ', $this->newCatalogNames['pipe']) }}. @endif
                </p>
            @endif

            @if (! $planSync->journal_accepted_at)
                @include('partials.cable-journal-diff', ['diff' => $diff])
            @endif
        </section>
        @endif

        {{-- Смета --}}
        <section class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Смета «{{ $planSync->scopeLabel() }}»: разница с уже выданным</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Выдана будет отдельной сметой и больше не изменится. Минус — потребность уменьшилась.
                        Считается только по сметам этой части: щиты и трассы не смешиваются.
                    </p>
                </div>
                @if (! $this->blockers)
                    <flux:button variant="primary" color="green" icon="document-check" wire:click="issueEstimate"
                        onclick="return confirm('Выдать смету закупщику? Править её потом будет нельзя.')">
                        Выдать смету
                    </flux:button>
                @endif
            </div>

            @foreach ($planSync->productSets as $set)
                <p class="mb-3 text-sm">
                    По этой отправке выдана
                    <a class="text-blue-600 underline" href="{{ route('project-product-set.items.index', $set) }}" wire:navigate>«{{ $set->name }}»</a>.
                </p>
            @endforeach

            @if ($this->blockers && $this->lines)
                <flux:callout icon="information-circle" class="mb-4">
                    @foreach ($this->blockers as $blocker)
                        <p>{{ $blocker }}</p>
                    @endforeach
                    @if (collect($this->lines)->contains(fn ($l) => ! $l['product']))
                        <p><a class="text-blue-600 underline" href="{{ route('plan-keys.index', ['unmapped' => 1]) }}" wire:navigate>Привязать ключи к товарам</a></p>
                    @endif
                </flux:callout>
            @endif

            @if ($this->lines)
                <div class="relative overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2">Раздел</th>
                                <th class="px-4 py-2">В 3D-плане</th>
                                <th class="px-4 py-2">Товар</th>
                                <th class="px-4 py-2 text-right">Выдано</th>
                                <th class="px-4 py-2 text-right">Нужно</th>
                                <th class="px-4 py-2 text-right">В смету</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->lines as $line)
                                <tr wire:key="line-{{ md5($line['key']) }}" class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $line['section'] }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $line['name'] }}</td>
                                    <td class="px-4 py-2">
                                        @if ($line['product'])
                                            {{ $line['product']->name }}
                                        @else
                                            <span class="text-red-600">не привязан</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ $this->qty($line['before']) }}</td>
                                    <td class="px-4 py-2 text-right">{{ $this->qty($line['after']) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold {{ $line['delta'] < 0 ? 'text-red-600' : 'text-green-700 dark:text-green-400' }}">
                                        {{ $line['delta'] > 0 ? '+' : '' }}{{ $this->qty($line['delta']) }} {{ $line['unit'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (! $planSync->productSets->count())
                <p class="text-sm text-gray-600 dark:text-gray-400">Потребность совпадает с выданным — выдавать нечего.</p>
            @endif
        </section>
    </div>
</div>
