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
    public function versions()
    {
        return $this->project->cableJournalVersions()->with('acceptedBy')->orderByDesc('number')->get();
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Версии кабельного журнала</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
            </div>
            <div class="flex gap-2">
                <flux:button icon="bolt" :href="route('projects.cable-items.index', $project)">Журнал</flux:button>
                <flux:button variant="ghost" :href="route('projects.plan-syncs.index', $project)">Отправки</flux:button>
            </div>
        </div>

        @forelse ($this->versions as $version)
            @php($c = $version->changes)
            <details wire:key="version-{{ $version->id }}" @if ($loop->first) open @endif
                class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                <summary class="cursor-pointer">
                    <span class="text-lg font-semibold">Версия №{{ $version->number }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        · {{ $version->created_at->format('d.m.Y H:i') }}{{ $version->acceptedBy ? ' · '.$version->acceptedBy->name : '' }}
                        · строк {{ count($version->rows) }}: новых {{ count($c['added']) }}, изменённых {{ count($c['changed']) }}, пропавших {{ count($c['removed']) }}
                    </span>
                </summary>
                @if ($loop->first && (count($c['added']) || count($c['changed'])))
                    {{-- Только у последней: строки старой версии в журнале уже могли смениться. --}}
                    <div class="mt-4">
                        <flux:button size="sm" color="emerald" icon="document-text"
                            :href="route('projects.cable-items.export-pdf', ['project' => $project, 'changed' => 1])">
                            Наклейки только новых и изменённых
                        </flux:button>
                    </div>
                @endif
                <div class="mt-4">
                    @include('partials.cable-journal-diff', ['diff' => $c])
                </div>
            </details>
        @empty
            <flux:callout>
                <flux:callout.heading>Версий ещё нет</flux:callout.heading>
                <p>Версия появляется, когда журнал из 3D-плана принят на странице отправки.</p>
            </flux:callout>
        @endforelse
    </div>
</div>
