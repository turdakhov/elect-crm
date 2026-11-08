<?php

namespace App\Services\Plan3d;

use App\Models\PlanKey;
use App\Models\PlanSync;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Смета из 3D-плана: потребность части минус то, что по ней уже выдано.
 *
 * 3D-план присылает ПОЛНУЮ потребность и не знает, что ушло закупщику, —
 * знать это должно одно место. «Выдано» — это полная потребность последней
 * выданной отправки этой части, а не строки смет: смету правят руками
 * (не закупаем, другой товар, другое количество), и правка не должна
 * возвращаться следующей доп-сметой. Доп-смета несёт только изменение
 * проекта, со знаком: минус — потребность уменьшилась.
 *
 * Разница считается по КЛЮЧАМ 3D-плана, а не по товарам: сменили бренд в
 * справочнике — проект от этого не изменился, и доп-сметы быть не должно.
 * Части (трассы, каждый щит) считаются порознь.
 */
class EstimateSync
{
    private const EPS = 0.005;

    /** Запомнить ключи отправки: на странице соответствий их привязывают к товарам. */
    public static function registerKeys(array $needs): void
    {
        foreach ($needs as $need) {
            PlanKey::updateOrCreate(
                ['key' => $need['key']],
                ['name' => $need['name'], 'unit' => $need['unit'], 'section' => $need['section'] ?? null],
            );
        }
    }

    /**
     * Сколько по каждому ключу уже выдано в этой части: потребность последней
     * выданной отправки. Удалили смету целиком — её отправка больше не выдана,
     * и разница снова считается от предыдущей.
     */
    public static function issued(Project $project, string $scope = PlanSync::TRACE_SCOPE): array
    {
        $sync = PlanSync::where('project_id', $project->id)->where('scope', $scope)
            ->whereNotNull('product_set_id')->latest('id')->first();
        $out = [];
        foreach ($sync?->needs ?? [] as $need) {
            $out[$need['key']] = round((float) $need['qty'], 2);
        }

        return $out;
    }

    /**
     * Строки разницы одной отправки.
     *
     * @return list<array{key: string, name: string, unit: string, section: ?string, before: float, after: float, delta: float, product: ?\App\Models\Product, skip: bool}>
     */
    public static function delta(PlanSync $sync): array
    {
        $issued = self::issued($sync->project, $sync->scope);
        $needs = [];
        foreach ($sync->needs as $need) {
            $needs[$need['key']] = $need;
        }
        $all = array_unique(array_merge(array_keys($needs), array_keys($issued)));
        $keys = PlanKey::with('product')->whereIn('key', $all)->get()->keyBy('key');

        $lines = [];
        foreach ($all as $key) {
            $after = round((float) ($needs[$key]['qty'] ?? 0), 2);
            $before = (float) ($issued[$key] ?? 0);
            $delta = round($after - $before, 2);
            if (abs($delta) < self::EPS) {
                continue;
            }
            $planKey = $keys->get($key);
            $lines[] = [
                'key' => $key,
                'name' => $needs[$key]['name'] ?? $planKey?->name ?? $key,
                'unit' => $needs[$key]['unit'] ?? $planKey?->unit ?? '',
                'section' => $needs[$key]['section'] ?? $planKey?->section,
                'before' => $before,
                'after' => $after,
                'delta' => $delta,
                'product' => $planKey?->product,
                'skip' => (bool) $planKey?->skip,
            ];
        }

        usort($lines, fn ($a, $b) => [$a['section'] ?? '', $a['name']] <=> [$b['section'] ?? '', $b['name']]);

        return $lines;
    }

    /** Что из разницы пойдёт в смету: всё, кроме «не закупаем». */
    public static function billable(array $lines): array
    {
        return array_values(array_filter($lines, fn ($l) => ! $l['skip']));
    }

    /** Почему смету по этой отправке выдать нельзя; пусто — можно. */
    public static function blockers(PlanSync $sync, ?array $lines = null): array
    {
        $lines ??= self::delta($sync);
        $out = [];
        if (! $sync->isLatest()) {
            $out[] = 'Есть отправка новее — смету выдают по ней.';
        }
        if (! self::billable($lines)) {
            $out[] = 'Разницы с выданным нет — выдавать нечего.';
        }

        return $out;
    }

    /** Отправки, которые можно выдать сейчас: последние в своих частях, с разницей. */
    public static function issuable(Project $project): Collection
    {
        return $project->planSyncs()->latest('id')->get()
            ->unique('scope')
            ->filter(fn (PlanSync $s) => self::billable(self::delta($s)) !== [])
            ->values();
    }

    public static function issue(PlanSync $sync, User $user): ProjectProductSet
    {
        return self::issueMany(collect([$sync]), $user);
    }

    /**
     * Выдать одной сметой разницу нескольких отправок — например, всех щитов.
     *
     * Строки одного ключа складываются: «Автомат 1P C16» щитов d15 и d13 — одна
     * строка 10 + 14, а из чего она сложена, видно в комментарии. Строки без
     * товара входят как есть — товар выберут в смете или он подставится сам,
     * когда ключ привяжут. «Не закупаем» не входит, но выданным считается: в
     * следующей доп-смете оно не всплывёт.
     *
     * @param  Collection<int, PlanSync>  $syncs
     */
    public static function issueMany(Collection $syncs, User $user): ProjectProductSet
    {
        if ($syncs->isEmpty()) {
            throw new RuntimeException('Не выбрано ни одной отправки.');
        }
        if ($syncs->pluck('project_id')->unique()->count() > 1) {
            throw new RuntimeException('Отправки разных проектов в одну смету не собираются.');
        }

        return DB::transaction(function () use ($syncs, $user) {
            $merged = [];
            $issuedBefore = false;
            foreach ($syncs as $sync) {
                $sync->refresh();
                if (! $sync->isLatest()) {
                    throw new RuntimeException('По части «'.$sync->scopeLabel().'» есть отправка новее — выдают её.');
                }
                $issuedBefore = $issuedBefore || self::issued($sync->project, $sync->scope) !== [];
                foreach (self::billable(self::delta($sync)) as $line) {
                    $m = $merged[$line['key']] ?? ['line' => $line, 'qty' => 0.0, 'from' => []];
                    $m['qty'] = round($m['qty'] + $line['delta'], 2);
                    $m['from'][] = self::shortScope($sync).': '.self::qty($line['delta']);
                    $merged[$line['key']] = $m;
                }
            }
            $merged = array_filter($merged, fn ($m) => abs($m['qty']) >= self::EPS);
            if (! $merged) {
                throw new RuntimeException('Разницы с выданным нет — выдавать нечего.');
            }

            $first = $syncs->first();
            $project = $first->project;
            $previous = PlanSync::where('project_id', $project->id)->whereIn('scope', $syncs->pluck('scope')->all())
                ->whereNotNull('product_set_id')->distinct()->count('product_set_id');
            $labels = $syncs->map(fn (PlanSync $s) => $s->scopeLabel())->implode(' + ');
            $set = ProjectProductSet::create([
                'project_id' => $project->id,
                'name' => mb_substr(($issuedBefore ? 'Доп. смета №'.$previous : 'Смета').' из 3D-плана — '.$labels, 0, 255),
                'comment' => 'Отправки №'.$syncs->pluck('id')->implode(', №').' от '.$first->created_at->format('d.m.Y H:i')
                    .'. Выдал '.$user->name.'.',
                'plan_sync_id' => $first->id,
                'issued_by' => $user->id,
            ]);

            foreach ($merged as $key => $m) {
                $from = count($m['from']) > 1 ? ' ('.implode(', ', $m['from']).')' : '';
                ProjectProductSetItem::create([
                    'project_product_set_id' => $set->id,
                    'product_id' => $m['line']['product']?->id,
                    'plan_key' => $key,
                    'quantity' => $m['qty'],
                    'comment' => mb_substr($m['line']['name'].$from, 0, 500),
                ]);
            }

            foreach ($syncs as $sync) {
                $sync->update(['product_set_id' => $set->id, 'issued_at' => now()]);
            }

            return $set;
        });
    }

    /** «щит d15» → «d15»: в комментарии строки места мало. */
    private static function shortScope(PlanSync $sync): string
    {
        return (string) preg_replace('/^щит\s+(\S+).*/u', '$1', $sync->scope);
    }

    private static function qty(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',');
    }
}
