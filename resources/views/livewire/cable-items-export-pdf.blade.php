<?php

use App\Models\{CableItem, Project};
use Livewire\Volt\Component;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    public function mount(): void
    {
        $project = request()->route('project');

        $cableItems = CableItem::where('project_id', $project->id)
            ->with(['cable', 'pipe'])
            ->orderBy('cable_id')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        // Группируем по cable_id
        $groupedByCable = $cableItems->groupBy('cable_id');

        $pdf = Pdf::loadView('cable-items-export-template', [
            'groupedByCable' => $groupedByCable,
            'projectName' => $project->name,
        ])->setPaper([0, 0, 2834, 2834]); // 1м x 1м в пиксела (28.34 px/cm)

        return $pdf->download("cable-items-{$project->id}.pdf");
    }
}; ?>
