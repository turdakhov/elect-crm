<?php

use App\Models\CableItem;
use App\Models\Project;
use Livewire\Volt\Component;

new class extends Component {
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function with(): array
    {
        $cableSummary = CableItem::query()
            ->where('project_id', $this->project->id)
            ->with('cable')
            ->get()
            ->groupBy('cable_id')
            ->map(fn ($items) => [
                'name' => $items->first()->cable?->name ?? '—',
                'count' => $items->count(),
                'length' => $items->sum('cable_length'),
            ])
            ->values();

        $pipeSummary = CableItem::query()
            ->where('project_id', $this->project->id)
            ->whereNotNull('pipe_id')
            ->with('pipe')
            ->get()
            ->groupBy('pipe_id')
            ->map(fn ($items) => [
                'name' => $items->first()->pipe?->name ?? '—',
                'count' => $items->count(),
                'length' => $items->sum('pipe_length'),
            ])
            ->values();

        return [
            'cableSummary' => $cableSummary,
            'pipeSummary' => $pipeSummary,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Сводка</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
        </div>
        <flux:button variant="filled" icon="arrow-left" :href="route('projects.cable-items.index', $project)">
            Назад к кабелям
        </flux:button>
    </div>

    <div class="space-y-6">
        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Кабели</h2>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Кабель</th>
                            <th scope="col" class="px-6 py-3 text-right">Кол-во позиций</th>
                            <th scope="col" class="px-6 py-3 text-right">Общая длина, м</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cableSummary as $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-6 py-4 text-right">{{ $row['count'] }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($row['length'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-400">Нет данных</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-neutral-200 dark:border-neutral-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Гофры</h2>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Гофра</th>
                            <th scope="col" class="px-6 py-3 text-right">Кол-во позиций</th>
                            <th scope="col" class="px-6 py-3 text-right">Общая длина, м</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pipeSummary as $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-6 py-4 text-right">{{ $row['count'] }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($row['length'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-400">Нет данных</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
