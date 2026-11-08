<?php

declare(strict_types=1);

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\CableJournalVersion;
use App\Models\Pipe;
use App\Models\PlanKey;
use App\Models\PlanSync;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\User;
use App\Services\Plan3d\EstimateSync;
use App\Services\Plan3d\JournalSync;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Volt\Volt;

const PLAN3D_TOKEN = 'test-plan3d-token';

beforeEach(function () {
    config(['services.plan3d.token' => PLAN3D_TOKEN]);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
});

function journalRow(array $over = []): array
{
    return array_merge([
        'floor' => '1 этаж', 'room' => 'Кухня', 'name' => 'роз 1', 'code' => 'c1',
        'cable' => '3*2,5', 'count' => 1, 'length' => 12.5, 'pipe' => 'd20 синяя', 'pipe_length' => 10,
    ], $over);
}

function need(string $key, float $qty, string $unit = 'м', string $name = ''): array
{
    return ['key' => $key, 'name' => $name ?: $key, 'unit' => $unit, 'qty' => $qty, 'section' => 'кабель'];
}

function sendSync($test, Project $project, array $journal, array $needs, string $token = PLAN3D_TOKEN, array $extra = [])
{
    return $test->withToken($token)->postJson(route('api.plan3d.syncs.store', $project), array_merge([
        'source_project' => 'kv-1',
        'source_name' => 'Квартира 1',
        'journal' => $journal,
        'needs' => $needs,
    ], $extra));
}

function sendPanel($test, Project $project, string $panel, array $needs)
{
    return sendSync($test, $project, [], $needs, PLAN3D_TOKEN, ['scope' => 'щит '.$panel, 'scope_name' => 'щит '.$panel.' — Подвал']);
}

describe('API', function () {
    it('без токена и с чужим токеном не пускает', function () {
        $this->getJson(route('api.plan3d.projects'))->assertStatus(401);
        $this->withToken('wrong')->getJson(route('api.plan3d.projects'))->assertStatus(401);
    });

    it('закрыт, если токен в CRM не задан', function () {
        config(['services.plan3d.token' => '']);
        $this->withToken('')->getJson(route('api.plan3d.projects'))->assertStatus(503);
    });

    it('отдаёт список проектов', function () {
        $this->withToken(PLAN3D_TOKEN)->getJson(route('api.plan3d.projects'))
            ->assertOk()
            ->assertJsonFragment(['id' => $this->project->id, 'name' => $this->project->name]);
    });

    it('принимает отправку, запоминает ключи и ничего не применяет', function () {
        sendSync($this, $this->project, [journalRow()], [need('кабель|ВВГнг 3×2.5', 412.6)])
            ->assertCreated()
            ->assertJsonPath('unchanged', false)
            ->assertJsonPath('journal.added', 1)
            ->assertJsonPath('estimate.lines', 1)
            ->assertJsonPath('estimate.unmapped', ['кабель|ВВГнг 3×2.5']);

        expect(PlanSync::count())->toBe(1)
            ->and(PlanKey::where('key', 'кабель|ВВГнг 3×2.5')->exists())->toBeTrue()
            ->and(CableItem::count())->toBe(0)
            ->and(ProjectProductSet::count())->toBe(0);
    });

    it('та же модель, отправленная дважды, — одна отправка', function () {
        sendSync($this, $this->project, [journalRow()], [need('k', 1)])->assertCreated();
        sendSync($this, $this->project, [journalRow()], [need('k', 1)])->assertOk()->assertJsonPath('unchanged', true);

        expect(PlanSync::count())->toBe(1);
    });

    it('отвергает кривой пакет', function () {
        sendSync($this, $this->project, [['room' => 'x']], [['key' => 'k']])->assertStatus(422);
    });
});

describe('разница журнала', function () {
    it('сопоставляет по наклейке, а не по коду', function () {
        // Перегенерация раздала новые id — но линия та же, и разницы нет.
        $diff = JournalSync::compare([journalRow(['code' => 'c1'])], [journalRow(['code' => 'c77', 'length' => 12.5])]);

        expect($diff['added'])->toBe([])
            ->and($diff['removed'])->toBe([])
            ->and($diff['changed'])->toHaveCount(1)
            ->and($diff['changed'][0]['fields'])->toBe(['code']);
    });

    it('видит новые, пропавшие и изменённые', function () {
        $diff = JournalSync::compare(
            [journalRow(), journalRow(['name' => 'споты', 'code' => 'c2'])],
            [journalRow(['length' => 14]), journalRow(['name' => 'бра', 'code' => 'c3'])],
        );

        expect($diff['added'])->toHaveCount(1)
            ->and($diff['added'][0]['name'])->toBe('бра')
            ->and($diff['removed'][0]['name'])->toBe('споты')
            ->and($diff['changed'][0]['fields'])->toBe(['length']);
    });

    it('узнаёт переименование по коду', function () {
        $diff = JournalSync::compare([journalRow(['name' => 'роз', 'code' => 'c5'])], [journalRow(['name' => 'роз 1', 'code' => 'c5'])]);

        expect($diff['added'])->toBe([])
            ->and($diff['removed'])->toBe([])
            ->and($diff['changed'][0]['fields'])->toBe(['name']);
    });

    it('регистр и ё в именах кабеля и гофры разницей не считает', function () {
        $diff = JournalSync::compare([journalRow(['pipe' => 'd20 чёрная'])], [journalRow(['pipe' => 'D20 черная'])]);

        expect($diff['changed'])->toBe([])->and($diff['unchanged'])->toBe(1);
    });

    it('не даёт принять журнал без названий и с повтором в комнате', function () {
        $errors = JournalSync::errors([journalRow(['name' => '']), journalRow(), journalRow(['code' => 'c9'])]);

        expect($errors)->toHaveCount(2)
            ->and($errors[0])->toContain('нет названия')
            ->and($errors[1])->toContain('уже есть');
    });
});

describe('принятие журнала', function () {
    it('заменяет журнал проекта целиком и пишет версию с разницей', function () {
        $old = CableItem::factory()->create(['project_id' => $this->project->id, 'name' => 'ручная строка', 'room' => 'Холл']);
        sendSync($this, $this->project, [journalRow(), journalRow(['name' => 'UTP', 'cable' => 'UTP', 'count' => 2, 'pipe' => 'd20 красная'])], []);

        $version = JournalSync::accept(PlanSync::first(), $this->user);

        $items = CableItem::where('project_id', $this->project->id)->with('cable', 'pipe')->get();
        expect($items)->toHaveCount(2)
            ->and($items->pluck('id'))->not->toContain($old->id)
            ->and($items->firstWhere('name', 'UTP')->cable_count)->toBe(2)
            ->and($items->firstWhere('name', 'UTP')->cable->name)->toBe('UTP')
            ->and(Cable::where('name', '3*2,5')->exists())->toBeTrue()
            ->and(Pipe::where('name', 'd20 красная')->exists())->toBeTrue()
            ->and($version->number)->toBe(1)
            ->and($version->changes['added'])->toHaveCount(2)
            ->and($version->changes['removed'][0]['name'])->toBe('ручная строка')
            ->and(PlanSync::first()->journal_accepted_at)->not->toBeNull();
    });

    it('берёт существующие кабели справочника, а не плодит дубли', function () {
        $pipe = Pipe::factory()->create(['name' => 'D20 Синяя']);
        sendSync($this, $this->project, [journalRow()], []);
        JournalSync::accept(PlanSync::first(), $this->user);

        expect(Pipe::count())->toBe(1)->and(CableItem::first()->pipe_id)->toBe($pipe->id);
    });

    it('старую отправку принять нельзя', function () {
        sendSync($this, $this->project, [journalRow()], []);
        sendSync($this, $this->project, [journalRow(['length' => 20])], []);

        expect(fn () => JournalSync::accept(PlanSync::orderBy('id')->first(), $this->user))
            ->toThrow(RuntimeException::class, 'новее');
    });

    it('вторая версия хранит только разницу со первой', function () {
        sendSync($this, $this->project, [journalRow(), journalRow(['name' => 'споты', 'code' => 'c2'])], []);
        JournalSync::accept(PlanSync::latest('id')->first(), $this->user);
        sendSync($this, $this->project, [journalRow(['length' => 15]), journalRow(['name' => 'споты', 'code' => 'c2'])], []);

        $version = JournalSync::accept(PlanSync::latest('id')->first(), $this->user);

        expect($version->number)->toBe(2)
            ->and($version->changes['added'])->toBe([])
            ->and($version->changes['changed'])->toHaveCount(1)
            ->and($version->changes['unchanged'])->toBe(1);
    });

    it('кнопка на странице отправки принимает журнал', function () {
        sendSync($this, $this->project, [journalRow()], []);
        $this->actingAs($this->user);

        Volt::test('plan-syncs-show', ['planSync' => PlanSync::first()])
            ->assertSee('Новые — нарезать')
            ->call('acceptJournal')
            ->assertSee('Принят');

        expect(CableJournalVersion::count())->toBe(1);
    });

    it('наклейки «только изменённые» печатают новые и изменённые строки последней версии', function () {
        sendSync($this, $this->project, [journalRow(), journalRow(['name' => 'споты', 'code' => 'c2'])], []);
        JournalSync::accept(PlanSync::latest('id')->first(), $this->user);
        sendSync($this, $this->project, [journalRow(['length' => 15]), journalRow(['name' => 'споты', 'code' => 'c2']), journalRow(['name' => 'бра', 'code' => 'c3'])], []);
        JournalSync::accept(PlanSync::latest('id')->first(), $this->user);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setOption')->andReturnSelf();
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('download')->andReturn(response('pdf', 200, ['content-type' => 'application/pdf']));
        Pdf::shouldReceive('loadView')->once()
            ->with('cable-items-export-template', \Mockery::on(fn ($data) => $data['groupedByCable']->flatten()->pluck('name')->sort()->values()->all() === ['бра', 'роз 1']))
            ->andReturn($pdf);

        $this->actingAs($this->user)
            ->get(route('projects.cable-items.export-pdf', ['project' => $this->project, 'changed' => 1]))
            ->assertSuccessful();
    });
});

describe('смета из 3D-плана', function () {
    beforeEach(function () {
        $this->cable = Product::factory()->create(['name' => 'ВВГнг-LS 3×2.5 Конкорд', 'unit' => 'м']);
        $this->socket = Product::factory()->create(['name' => 'Розетка Schneider AtlasDesign', 'unit' => 'шт']);
    });

    function mapKeys(array $map): void
    {
        foreach ($map as $key => $product) {
            PlanKey::where('key', $key)->update(['product_id' => $product->id]);
        }
    }

    it('первая выдача — вся потребность, и смета закрыта от правки', function () {
        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 412.6), need('прибор|socket', 24, 'шт')]);
        mapKeys(['кабель|ВВГнг 3×2.5' => $this->cable, 'прибор|socket' => $this->socket]);

        $set = EstimateSync::issue(PlanSync::first(), $this->user);

        expect($set->name)->toBe('Смета из 3D-плана')
            ->and($set->isLocked())->toBeTrue()
            ->and($set->items()->where('product_id', $this->cable->id)->first()->quantity)->toBe(412.6)
            ->and($set->items()->where('product_id', $this->socket->id)->first()->quantity)->toBe(24.0);
    });

    it('после изменения выдаёт доп-смету с разницей, и с минусом тоже', function () {
        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 400), need('прибор|socket', 24, 'шт')]);
        mapKeys(['кабель|ВВГнг 3×2.5' => $this->cable, 'прибор|socket' => $this->socket]);
        EstimateSync::issue(PlanSync::first(), $this->user);

        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 438.2), need('прибор|socket', 22, 'шт')]);
        $set = EstimateSync::issue(PlanSync::latest('id')->first(), $this->user);

        expect($set->name)->toBe('Доп. смета №1 из 3D-плана')
            ->and($set->items()->where('product_id', $this->cable->id)->first()->quantity)->toBe(38.2)
            ->and($set->items()->where('product_id', $this->socket->id)->first()->quantity)->toBe(-2.0)
            // Сумма всех смет равна последней потребности.
            ->and(EstimateSync::issued($this->project))->toBe(['кабель|ВВГнг 3×2.5' => 438.2, 'прибор|socket' => 22.0]);
    });

    it('пропавший из 3D-плана ключ уходит в минус целиком', function () {
        sendSync($this, $this->project, [], [need('прибор|socket', 24, 'шт')]);
        mapKeys(['прибор|socket' => $this->socket]);
        EstimateSync::issue(PlanSync::first(), $this->user);

        sendSync($this, $this->project, [], []);
        $lines = EstimateSync::delta(PlanSync::latest('id')->first());

        expect($lines)->toHaveCount(1)->and($lines[0]['delta'])->toBe(-24.0);
    });

    it('смена бренда в справочнике доп-сметы не создаёт', function () {
        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 400)]);
        mapKeys(['кабель|ВВГнг 3×2.5' => $this->cable]);
        EstimateSync::issue(PlanSync::first(), $this->user);

        mapKeys(['кабель|ВВГнг 3×2.5' => Product::factory()->create(['unit' => 'м'])]);
        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 400)]);

        expect(EstimateSync::delta(PlanSync::latest('id')->first()))->toBe([]);
    });

    it('без привязки к товару выдать нельзя', function () {
        sendSync($this, $this->project, [], [need('кабель|ВВГнг 3×2.5', 400)]);

        expect(fn () => EstimateSync::issue(PlanSync::first(), $this->user))
            ->toThrow(RuntimeException::class, 'Не привязаны');
    });

    it('сводка «план и факт» складывает выданные сметы с минусами', function () {
        sendSync($this, $this->project, [], [need('прибор|socket', 24, 'шт')]);
        mapKeys(['прибор|socket' => $this->socket]);
        EstimateSync::issue(PlanSync::first(), $this->user);
        sendSync($this, $this->project, [], [need('прибор|socket', 20, 'шт')]);
        EstimateSync::issue(PlanSync::latest('id')->first(), $this->user);
        $this->actingAs($this->user);

        $row = Volt::test('project-products-summary', ['project' => $this->project])->get('products')->first();

        expect($row['estimate_quantity'])->toBe(20.0);
    });

    it('выданную смету не открыть на правку и не удалить', function () {
        sendSync($this, $this->project, [], [need('прибор|socket', 24, 'шт')]);
        mapKeys(['прибор|socket' => $this->socket]);
        $set = EstimateSync::issue(PlanSync::first(), $this->user);
        $this->actingAs($this->user);

        $this->get(route('project-product-set.items.create', $set))->assertForbidden();
        $this->get(route('project-product-set.edit', $set))->assertForbidden();
        $this->get(route('project-product-set-items.edit', $set->items()->first()))->assertForbidden();
        Volt::test('project-product-set-index', ['project' => $this->project])->call('deleteSet', $set->id)->assertForbidden();

        expect(ProjectProductSet::find($set->id))->not->toBeNull();
    });

    it('кнопка «Выдать смету» на странице отправки', function () {
        sendSync($this, $this->project, [], [need('прибор|socket', 24, 'шт')]);
        $this->actingAs($this->user);

        Volt::test('plan-syncs-show', ['planSync' => PlanSync::first()])
            ->assertSee('не привязан')
            ->assertDontSee('Выдать смету</');

        mapKeys(['прибор|socket' => $this->socket]);
        Volt::test('plan-syncs-show', ['planSync' => PlanSync::first()])
            ->call('issueEstimate')
            ->assertRedirect();

        expect(ProjectProductSetItem::where('plan_key', 'прибор|socket')->value('quantity'))->toBe(24.0);
    });

    it('страница ключей привязывает товар', function () {
        sendSync($this, $this->project, [], [need('прибор|socket', 24, 'шт')]);
        $this->actingAs($this->user);
        $key = PlanKey::first();

        Volt::test('plan-keys-index')
            ->call('edit', $key->id)
            ->set('productSearch', 'Atlas')
            ->assertSee('Розетка Schneider AtlasDesign')
            ->call('choose', $this->socket->id);

        expect($key->fresh()->product_id)->toBe($this->socket->id);
    });
});

describe('части: трассы и щиты', function () {
    beforeEach(function () {
        $this->wire = Product::factory()->create(['name' => 'ВВГнг 5×4', 'unit' => 'м']);
        $this->breaker = Product::factory()->create(['name' => 'Автомат C16', 'unit' => 'шт']);
    });

    it('у щита журнала не бывает', function () {
        sendSync($this, $this->project, [journalRow()], [need('k', 1)], PLAN3D_TOKEN, ['scope' => 'щит d15'])
            ->assertStatus(422);
    });

    it('часть называется только «трассы» или «щит …»', function () {
        sendSync($this, $this->project, [], [need('k', 1)], PLAN3D_TOKEN, ['scope' => 'всё подряд'])
            ->assertStatus(422);
    });

    it('сметы щита и трасс не вычитаются друг из друга, даже на одном товаре', function () {
        // Кабель трасс и провод щита привязаны к одному товару.
        sendSync($this, $this->project, [], [need('кабель|5*4', 58)]);
        sendPanel($this, $this->project, 'd15', [need('щит: провод|Кабель 5×4 — в щите', 12)])
            ->assertJsonPath('scope', 'щит d15')
            ->assertJsonPath('scope_name', 'щит d15 — Подвал');
        mapKeys(['кабель|5*4' => $this->wire, 'щит: провод|Кабель 5×4 — в щите' => $this->wire]);

        $trace = EstimateSync::issue(PlanSync::where('scope', 'трассы')->first(), $this->user);
        $panel = EstimateSync::issue(PlanSync::where('scope', 'щит d15')->first(), $this->user);

        expect($trace->name)->toBe('Смета из 3D-плана')
            ->and($panel->name)->toBe('Смета из 3D-плана — щит d15 — Подвал')
            ->and($panel->items()->first()->quantity)->toBe(12.0)
            ->and(EstimateSync::issued($this->project, 'трассы'))->toBe(['кабель|5*4' => 58.0])
            ->and(EstimateSync::issued($this->project, 'щит d15'))->toBe(['щит: провод|Кабель 5×4 — в щите' => 12.0]);
    });

    it('у каждого щита свои доп-сметы', function () {
        sendPanel($this, $this->project, 'd15', [need('щит: аппараты|Автомат 1P C16', 10, 'шт')]);
        sendPanel($this, $this->project, 'd13', [need('щит: аппараты|Автомат 1P C16', 14, 'шт')]);
        mapKeys(['щит: аппараты|Автомат 1P C16' => $this->breaker]);
        EstimateSync::issue(PlanSync::where('scope', 'щит d15')->first(), $this->user);
        EstimateSync::issue(PlanSync::where('scope', 'щит d13')->first(), $this->user);

        sendPanel($this, $this->project, 'd15', [need('щит: аппараты|Автомат 1P C16', 11, 'шт')]);
        $extra = EstimateSync::issue(PlanSync::where('scope', 'щит d15')->latest('id')->first(), $this->user);

        expect($extra->name)->toBe('Доп. смета №1 из 3D-плана — щит d15 — Подвал')
            ->and($extra->items()->first()->quantity)->toBe(1.0)
            ->and(EstimateSync::issued($this->project, 'щит d13'))->toBe(['щит: аппараты|Автомат 1P C16' => 14.0]);
    });

    it('отправка щита не делает устаревшей отправку трасс', function () {
        sendSync($this, $this->project, [journalRow()], []);
        sendPanel($this, $this->project, 'd15', [need('k', 1)]);

        $trace = PlanSync::where('scope', 'трассы')->first();
        expect($trace->isLatest())->toBeTrue();
        JournalSync::accept($trace, $this->user);
        expect(CableItem::count())->toBe(1);
    });

    it('журнал щита принять нельзя, и страница его не показывает', function () {
        sendPanel($this, $this->project, 'd15', [need('k', 1)]);
        $sync = PlanSync::first();
        $this->actingAs($this->user);

        expect(fn () => JournalSync::accept($sync, $this->user))->toThrow(RuntimeException::class, 'журнала нет');
        Volt::test('plan-syncs-show', ['planSync' => $sync])
            ->assertSee('щит d15 — Подвал')
            ->assertDontSee('Кабельный журнал');
    });
});
