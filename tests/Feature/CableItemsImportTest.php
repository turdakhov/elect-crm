<?php

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\Pipe;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;

it('imports cable items from csv', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $pipe = Pipe::factory()->create(['name' => 'Гофра 16']);
    $cableA = Cable::factory()->create(['name' => '3x2.5']);
    $cableB = Cable::factory()->create(['name' => 'UTP']);

    $content = ";;;гофра;3x2.5;UTP\n".
        "Цоколь;Кинотеатр;Ввод свет;9,6;12,6;\n".
        "Цоколь;Кинотеатр;UTP точка;1,2;;5,5\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $content);

    Volt::test('cable-items-import', ['project' => $project])
        ->set('pipe_id', $pipe->id)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors();

    $items = CableItem::where('project_id', $project->id)->get();
    expect($items)->toHaveCount(2);

    $first = $items->firstWhere('cable_id', $cableA->id);
    $second = $items->firstWhere('cable_id', $cableB->id);

    expect($first)->not->toBeNull();
    expect($first->cable_length)->toBe(12.6);
    expect($first->pipe_length)->toBe(9.6);

    expect($second)->not->toBeNull();
    expect($second->cable_length)->toBe(5.5);
    expect($second->pipe_length)->toBe(1.2);
});

it('rejects csv when cable header is missing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $pipe = Pipe::factory()->create(['name' => 'Гофра 16']);

    $content = ";;;гофра;MissingCable\n".
        "Цоколь;Кинотеатр;Ввод свет;9,6;12,6\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $content);

    Volt::test('cable-items-import', ['project' => $project])
        ->set('pipe_id', $pipe->id)
        ->set('file', $file)
        ->call('import')
        ->assertHasErrors(['file']);

    expect(CableItem::where('project_id', $project->id)->count())->toBe(0);
});

it('imports comma delimited csv with quoted decimal values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();
    $pipe = Pipe::factory()->create(['name' => 'Гофра 16']);
    $cable = Cable::factory()->create(['name' => '3x2.5']);

    $content = ",,,гофра,3x2.5\n".
        "Цоколь,Кинотеатр,Розетка у входа,\"3,5\",\"4,5\"\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $content);

    Volt::test('cable-items-import', ['project' => $project])
        ->set('pipe_id', $pipe->id)
        ->set('file', $file)
        ->call('import')
        ->assertHasNoErrors();

    $item = CableItem::where('project_id', $project->id)
        ->where('cable_id', $cable->id)
        ->first();

    expect($item)->not->toBeNull();
    expect($item->pipe_length)->toBe(3.5);
    expect($item->cable_length)->toBe(4.5);
});
