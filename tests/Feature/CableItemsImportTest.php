<?php

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\Pipe;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;

const IMPORT_HEADER = "Этаж;Комната;Название;Кабель;Кол-во;Длина кабеля, м;Гофра;Длина гофры, м\n";

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->project = Project::factory()->create();
});

function importCsv(Project $project, string $content): \Livewire\Features\SupportTesting\Testable
{
    return Volt::test('cable-items-import', ['project' => $project])
        ->set('file', UploadedFile::fake()->createWithContent('import.csv', $content))
        ->call('import');
}

it('imports rows with cable, count and pipe from each row', function () {
    $cableLight = Cable::factory()->create(['name' => '3*1,5']);
    $cableUtp = Cable::factory()->create(['name' => 'UTP']);
    $pipe = Pipe::factory()->create(['name' => 'd20 черная']);

    $content = "\xEF\xBB\xBF".IMPORT_HEADER.
        "2 этаж;Гостевая спальня;ввод свет;3*1,5;1;10,57;d20 чёрная;8,57\n".
        "2 этаж;Гостевая спальня;выкл у входа;UTP;4;11,08;D20 Чёрная;6,88\n".
        ";;;;;;;\n";

    importCsv($this->project, $content)->assertHasNoErrors();

    $items = CableItem::where('project_id', $this->project->id)->get();
    expect($items)->toHaveCount(2);

    $light = $items->firstWhere('cable_id', $cableLight->id);
    expect($light->floor)->toBe('2 этаж')
        ->and($light->room)->toBe('Гостевая спальня')
        ->and($light->name)->toBe('ввод свет')
        ->and($light->cable_count)->toBe(1)
        ->and($light->cable_length)->toBe(10.57)
        ->and($light->pipe_id)->toBe($pipe->id)
        ->and($light->pipe_length)->toBe(8.57);

    $utp = $items->firstWhere('cable_id', $cableUtp->id);
    expect($utp->cable_count)->toBe(4)
        ->and($utp->cable_length)->toBe(11.08)
        ->and($utp->pipe_id)->toBe($pipe->id);
});

it('treats empty count as one and empty pipe length as zero', function () {
    Cable::factory()->create(['name' => 'UTP']);
    Pipe::factory()->create(['name' => 'd20 черная']);

    importCsv($this->project, IMPORT_HEADER."1 этаж;Холл;WiFi;UTP;;10;d20 черная;\n")->assertHasNoErrors();

    $item = CableItem::where('project_id', $this->project->id)->sole();
    expect($item->cable_count)->toBe(1)
        ->and($item->pipe_length)->toBe(0.0);
});

it('rejects the whole file and lists cables and pipes missing from catalogs', function () {
    Cable::factory()->create(['name' => '3*1,5']);
    Pipe::factory()->create(['name' => 'd20 черная']);

    $content = IMPORT_HEADER.
        "2 этаж;Спальня;люстра;3*1,5;1;2,86;d20 черная;1,86\n".
        "2 этаж;Спальня;штора;5*0,75;1;12,60;d20 черная;11,40\n".
        "2 этаж;Спальня;штора 2;5*0,75;1;11,85;d32 синяя;9,65\n";

    importCsv($this->project, $content)
        ->assertHasErrors(['file'])
        ->assertSee('Кабели не найдены в справочнике: 5*0,75.')
        ->assertSee('Гофры не найдены в справочнике: d32 синяя.');

    expect(CableItem::where('project_id', $this->project->id)->count())->toBe(0);
});

it('rejects invalid count and length values', function (string $row, string $message) {
    Cable::factory()->create(['name' => 'UTP']);
    Pipe::factory()->create(['name' => 'd20 черная']);

    importCsv($this->project, IMPORT_HEADER.$row."\n")
        ->assertHasErrors(['file'])
        ->assertSee($message);

    expect(CableItem::count())->toBe(0);
})->with([
    'zero count' => ['1;Холл;WiFi;UTP;0;10;d20 черная;5', 'Строка 2: некорректное количество кабелей.'],
    'fractional count' => ['1;Холл;WiFi;UTP;1,5;10;d20 черная;5', 'Строка 2: некорректное количество кабелей.'],
    'empty cable length' => ['1;Холл;WiFi;UTP;1;;d20 черная;5', 'Строка 2: некорректная длина кабеля.'],
    'text pipe length' => ['1;Холл;WiFi;UTP;1;10;d20 черная;abc', 'Строка 2: некорректная длина гофры.'],
    'missing room' => ['1;;WiFi;UTP;1;10;d20 черная;5', 'Строка 2: заполните этаж, комнату и название.'],
]);

it('rejects file with too few columns', function () {
    importCsv($this->project, ";;;гофра;3x2.5;UTP\nЦоколь;Кинотеатр;Ввод свет;9,6;12,6;\n")
        ->assertHasErrors(['file']);

    expect(CableItem::count())->toBe(0);
});

it('imports comma delimited csv with quoted decimals', function () {
    $cable = Cable::factory()->create(['name' => '3*2,5']);
    Pipe::factory()->create(['name' => 'd20 черная']);

    $content = "Этаж,Комната,Название,Кабель,Кол-во,Длина кабеля,Гофра,Длина гофры\n".
        "Цоколь,Кинотеатр,Розетка,\"3*2,5\",2,\"4,5\",d20 черная,\"3,5\"\n";

    importCsv($this->project, $content)->assertHasNoErrors();

    $item = CableItem::where('cable_id', $cable->id)->sole();
    expect($item->cable_count)->toBe(2)
        ->and($item->cable_length)->toBe(4.5)
        ->and($item->pipe_length)->toBe(3.5);
});
