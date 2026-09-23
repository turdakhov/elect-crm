<?php

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\Pipe;
use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->project = Project::factory()->create();
    $this->cable = Cable::factory()->create(['name' => 'UTP']);
    $this->pipe = Pipe::factory()->create(['name' => 'd20 черная']);
});

it('defaults cable count to one when not provided', function () {
    $item = CableItem::create([
        'project_id' => $this->project->id,
        'floor' => '1',
        'room' => 'Холл',
        'name' => 'WiFi',
        'cable_id' => $this->cable->id,
        'pipe_id' => $this->pipe->id,
        'cable_length' => 10,
        'pipe_length' => 5,
    ]);

    expect($item->fresh()->cable_count)->toBe(1);
});

it('multiplies cable length by cable count in summary', function () {
    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'cable_id' => $this->cable->id,
        'pipe_id' => $this->pipe->id,
        'cable_count' => 4,
        'cable_length' => 10,
        'pipe_length' => 8,
    ]);
    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'cable_id' => $this->cable->id,
        'pipe_id' => $this->pipe->id,
        'cable_count' => 1,
        'cable_length' => 2.5,
        'pipe_length' => 1,
    ]);

    $component = Volt::test('cable-items-summary', ['project' => $this->project]);

    expect($component->viewData('cableSummary')->first()['length'])->toEqual(42.5)
        ->and($component->viewData('pipeSummary')->first()['length'])->toEqual(9.0);
});

it('creates and updates cable count through forms', function () {
    Volt::test('cable-items-create', ['project' => $this->project])
        ->set('floor', '1')
        ->set('room', 'Холл')
        ->set('name', 'WiFi')
        ->set('code', 'c181')
        ->set('cable_id', $this->cable->id)
        ->set('pipe_id', $this->pipe->id)
        ->set('cable_count', 3)
        ->set('cable_length', '10')
        ->set('pipe_length', '5')
        ->call('save')
        ->assertHasNoErrors();

    $item = CableItem::sole();
    expect($item->cable_count)->toBe(3)
        ->and($item->code)->toBe('c181');

    Volt::test('cable-items-edit', ['cableItem' => $item])
        ->assertSet('cable_count', 3)
        ->set('cable_count', 0)
        ->call('submit')
        ->assertHasErrors(['cable_count'])
        ->set('cable_count', 5)
        ->call('submit')
        ->assertHasNoErrors();

    expect($item->fresh()->cable_count)->toBe(5);
});

it('shows cable count on pdf labels only when greater than one', function () {
    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'cable_id' => $this->cable->id,
        'pipe_id' => $this->pipe->id,
        'name' => 'подвес у двери',
        'code' => 'c121',
        'cable_count' => 3,
        'cable_length' => 12.99,
    ]);
    CableItem::factory()->create([
        'project_id' => $this->project->id,
        'cable_id' => $this->cable->id,
        'pipe_id' => $this->pipe->id,
        'cable_count' => 1,
        'cable_length' => 7.5,
    ]);

    $html = view('cable-items-export-template', [
        'groupedByCable' => CableItem::with(['cable', 'pipe'])->get()->groupBy('cable_id'),
        'projectName' => $this->project->name,
        'floorLabel' => 'Все этажи',
    ])->render();

    expect($html)->toContain('13.0</span><span class="cell-count">x3</span>')
        ->and($html)->toContain('7.5</span>')
        ->and($html)->not->toContain('x1<')
        ->and($html)->toContain('<div class="cell-name">подвес у двери <span class="cell-code">c121</span></div>');
});
