<?php

namespace App\Http\Controllers;

use App\Models\CableItem;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CableItemsExportController extends Controller
{
    public function __invoke(Project $project, Request $request): Response
    {
        $floor = $request->query('floor');

        $cableItems = CableItem::where('project_id', $project->id)
            ->with(['cable', 'pipe'])
            ->when($floor, function ($query) use ($floor) {
                $query->where('floor', $floor);
            })
            ->orderBy('cable_id')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        $groupedByCable = $cableItems->groupBy('cable_id');

        $pdf = Pdf::loadView('cable-items-export-template', [
            'groupedByCable' => $groupedByCable,
            'projectName' => $project->name,
            'floorLabel' => $floor ?: 'Все этажи',
        ])
            ->setOption('encoding', 'UTF-8')
            ->setPaper([0, 0, 2834, 8502]);

        $projectLabel = $this->normalizeFilenamePart($project->name) ?: "проект-{$project->id}";
        $floorLabel = $floor ? $this->normalizeFilenamePart($floor) : 'все-этажи';

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
