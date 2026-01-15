<?php

use App\Models\{CableItem, Project};
use Livewire\Volt\Component;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component {
    public Project $project;

    public function mount(Project $project)
    {
        $this->project = $project;
    }

    public function exportPdf()
    {
        $cableItems = CableItem::where('project_id', $this->project->id)
            ->with(['cable', 'pipe'])
            ->orderBy('cable_id')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        // Группируем по cable_id
        $groupedByCable = $cableItems->groupBy('cable_id');

        $pdf = Pdf::loadView('cable-items-export-template', [
            'groupedByCable' => $groupedByCable,
            'projectName' => $this->project->name,
        ])->setPaper([0, 0, 2834, 2834]); // 1м x 1м в пиксела (28.34 px/cm)

        return $pdf->download("cable-items-{$this->project->id}.pdf");
    }
}; ?>

<div class="flex justify-end gap-2 p-6">
    <flux:button variant="primary" wire:click="exportPdf" icon="arrow-down">
        Экспортировать в PDF
    </flux:button>
</div>
