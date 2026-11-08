<?php

namespace App\Services\Plan3d;

use App\Models\PlanKey;
use App\Models\PlanSync;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Смета из 3D-плана: потребность минус всё, что уже выдано.
 *
 * 3D-план присылает ПОЛНУЮ потребность и не знает, что ушло закупщику, —
 * знать это должно одно место. Выданная смета не меняется: разница выдаётся
 * новой доп-сметой, со знаком. Минус — потребность уменьшилась; так сумма всех
 * смет проекта всегда равна последней выданной потребности.
 *
 * Разница считается по КЛЮЧАМ 3D-плана, а не по товарам: сменили бренд кабеля
 * в справочнике — проект от этого не изменился, и доп-сметы быть не должно.
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
     * Сколько по каждому ключу уже выдано сметами из 3D-плана — в этой части.
     * Провод щита и кабель трасс считаются порознь, даже если это один товар.
     */
    public static function issued(Project $project, string $scope = PlanSync::TRACE_SCOPE): array
    {
        return ProjectProductSetItem::query()
            ->whereHas('projectProductSet', fn ($q) => $q->where('project_id', $project->id)
                ->whereHas('planSync', fn ($q) => $q->where('scope', $scope)))
            ->where('plan_key', '!=', '')
            ->get(['plan_key', 'quantity'])
            ->groupBy('plan_key')
            ->map(fn ($items) => round($items->sum('quantity'), 2))
            ->all();
    }

    /**
     * Строки будущей доп-сметы.
     *
     * @return list<array{key: string, name: string, unit: string, section: ?string, before: float, after: float, delta: float, product: ?\App\Models\Product}>
     */
    public static function delta(PlanSync $sync): array
    {
        $issued = self::issued($sync->project, $sync->scope);
        $needs = [];
        foreach ($sync->needs as $need) {
            $needs[$need['key']] = $need;
        }
        $keys = PlanKey::with('product')->whereIn('key', array_unique(array_merge(array_keys($needs), array_keys($issued))))->get()->keyBy('key');

        $lines = [];
        foreach (array_unique(array_merge(array_keys($needs), array_keys($issued))) as $key) {
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
            ];
        }

        usort($lines, fn ($a, $b) => [$a['section'] ?? '', $a['name']] <=> [$b['section'] ?? '', $b['name']]);

        return $lines;
    }

    /** Почему смету по этой отправке выдать нельзя; пусто — можно. */
    public static function blockers(PlanSync $sync, ?array $lines = null): array
    {
        $lines ??= self::delta($sync);
        $out = [];
        if (! $sync->isLatest()) {
            $out[] = 'Есть отправка новее — смету выдают по ней.';
        }
        if (! $lines) {
            $out[] = 'Разницы с выданным нет — выдавать нечего.';
        }
        $unmapped = array_filter($lines, fn ($l) => ! $l['product']);
        if ($unmapped) {
            $out[] = 'Не привязаны к товарам: '.implode(', ', array_map(fn ($l) => $l['name'], $unmapped)).'.';
        }

        return $out;
    }

    /**
     * Выдать смету: зафиксировать разницу отдельной сметой. После этого она
     * только для чтения — товар в строке запоминается на момент выдачи и смену
     * соответствия не переживает. Так закупщик видит ровно то, что ему выдали.
     */
    public static function issue(PlanSync $sync, User $user): ProjectProductSet
    {
        return DB::transaction(function () use ($sync, $user) {
            $lines = self::delta($sync);
            $blockers = self::blockers($sync, $lines);
            if ($blockers) {
                throw new RuntimeException(implode(' ', $blockers));
            }

            $project = $sync->project;
            $previous = $project->productSets()->whereHas('planSync', fn ($q) => $q->where('scope', $sync->scope))->count();
            $part = $sync->hasJournal() ? '' : ' — '.$sync->scopeLabel();
            $set = ProjectProductSet::create([
                'project_id' => $project->id,
                'name' => ($previous === 0 ? 'Смета из 3D-плана' : 'Доп. смета №'.$previous.' из 3D-плана').$part,
                'comment' => 'Отправка №'.$sync->id.' от '.$sync->created_at->format('d.m.Y H:i')
                    .', модель '.substr($sync->fingerprint, 0, 8).'. Выдал '.$user->name.'.',
                'plan_sync_id' => $sync->id,
                'issued_by' => $user->id,
            ]);

            foreach ($lines as $line) {
                ProjectProductSetItem::create([
                    'project_product_set_id' => $set->id,
                    'product_id' => $line['product']->id,
                    'plan_key' => $line['key'],
                    'quantity' => $line['delta'],
                    'comment' => mb_substr($line['name'], 0, 500),
                ]);
            }

            return $set;
        });
    }
}
