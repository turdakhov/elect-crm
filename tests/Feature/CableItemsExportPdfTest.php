<?php

declare(strict_types=1);

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\Pipe;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * @property User $user
 * @property Project $project
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
});

test('пользователь может скачать pdf с кабелями проекта', function () {
    $response = $this->actingAs($this->user)
        ->get(route('projects.cable-items.export-pdf', $this->project));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('экспорт учитывает фильтр по этажу', function () {
    $cable = Cable::factory()->create(['name' => 'Test Cable']);
    $pipe = Pipe::factory()->create(['name' => 'Test Pipe']);

    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'floor' => 'FLOOR_AAA',
        'room' => 'Room 1',
        'name' => 'Item A',
        'cable_id' => $cable->id,
        'pipe_id' => $pipe->id,
    ]);

    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'floor' => 'FLOOR_BBB',
        'room' => 'Room 2',
        'name' => 'Item B',
        'cable_id' => $cable->id,
        'pipe_id' => $pipe->id,
    ]);

    $pdfMock = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('setOption')->andReturnSelf();
    $pdfMock->shouldReceive('setPaper')->andReturnSelf();
    $pdfMock->shouldReceive('download')->andReturn(response('pdf', 200, ['content-type' => 'application/pdf']));

    Pdf::shouldReceive('loadView')
        ->once()
        ->with('cable-items-export-template', \Mockery::on(function ($data) {
            $items = $data['groupedByCable']->flatten();

            return $items->every(fn ($item) => $item->floor === 'FLOOR_AAA');
        }))
        ->andReturn($pdfMock);

    $response = $this->actingAs($this->user)
        ->get(route('projects.cable-items.export-pdf', ['project' => $this->project, 'floor' => 'FLOOR_AAA']));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});
