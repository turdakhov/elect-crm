<?php

namespace App\Http\Controllers;

use App\Models\CableItem;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class CableItemsExportController extends Controller
{
    public function __invoke(Project $project): Response
    {
        $cableItems = CableItem::where('project_id', $project->id)
            ->with(['cable', 'pipe'])
            ->orderBy('cable_id')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        $groupedByCable = $cableItems->groupBy('cable_id');

        $pdf = Pdf::loadView('cable-items-export-template', [
            'groupedByCable' => $groupedByCable,
            'projectName' => $project->name,
        ])
            ->setOption('encoding', 'UTF-8')
            ->setPaper([0, 0, 2834, 2834]);

        return $pdf->download("cable-items-{$project->id}.pdf");
    }
}
