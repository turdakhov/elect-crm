<?php

namespace App\Http\Controllers;

use App\Models\CableItem;
use App\Models\Project;
use App\Services\Plan3d\JournalSync;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CableItemsExportController extends Controller
{
    public function __invoke(Project $project, Request $request): Response
    {
        $floor = $request->query('floor');
        $onlyChanged = $request->boolean('changed');

        $cableItems = CableItem::where('project_id', $project->id)
            ->with(['cable', 'pipe'])
            ->when($floor, function ($query) use ($floor) {
                $query->where('floor', $floor);
            })
            ->orderBy('cable_id')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        if ($onlyChanged) {
            // Наклейки только тех линий, что последняя версия журнала добавила
            // или изменила: остальные уже напечатаны и наклеены.
            $version = $project->cableJournalVersions()->orderByDesc('number')->first();
            $changes = $version?->changes ?? ['added' => [], 'changed' => []];
            $keys = collect($changes['added'])
                ->merge(collect($changes['changed'])->pluck('after'))
                ->map(fn ($row) => JournalSync::key($row))
                ->flip();
            $cableItems = $cableItems
                ->filter(fn (CableItem $i) => $keys->has(JournalSync::key(['floor' => $i->floor, 'room' => $i->room, 'name' => $i->name])))
                ->values();
        }

        $groupedByCable = $cableItems->groupBy('cable_id');

        $pdf = Pdf::loadView('cable-items-export-template', [
            'groupedByCable' => $groupedByCable,
            'projectName' => $project->name,
            'floorLabel' => $floor ?: 'Все этажи',
        ])
            ->setOption('encoding', 'UTF-8')
            ->setPaper([0, 0, 2834, 8502]);

        $projectLabel = $this->normalizeFilenamePart($project->name) ?: "проект-{$project->id}";
        $floorLabel = ($floor ? $this->normalizeFilenamePart($floor) : 'все-этажи').($onlyChanged ? '-изменённые' : '');

        return $pdf->download("провода-{$projectLabel}-{$floorLabel}.pdf");
    }

    private function normalizeFilenamePart(string $value): string
    {
        $clean = preg_replace('/[\\/\?%\*:\|"<>\.]+/u', ' ', $value);
        $clean = preg_replace('/\s+/u', '-', trim((string) $clean));
        $clean = preg_replace('/-+/u', '-', $clean);

        return trim((string) $clean, '-');
    }
}
