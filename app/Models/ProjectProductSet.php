<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectProductSet extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectProductSetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'comment',
        'plan_sync_id',
        'issued_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function planSync(): BelongsTo
    {
        return $this->belongsTo(PlanSync::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Смета выдана из 3D-плана. Править её можно: выданное помнят отправки
     * (`EstimateSync::issued`), и ручная правка доп-сметой не вернётся.
     */
    public function fromPlan(): bool
    {
        return $this->plan_sync_id !== null;
    }

    /** Отправки 3D-плана, собранные в эту смету. */
    public function planSyncs(): HasMany
    {
        return $this->hasMany(PlanSync::class, 'product_set_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectProductSetItem::class);
    }

    /**
     * Строки по разделам — для страницы сметы и для PDF, чтобы итоги в них
     * не разошлись. Строки 3D-плана идут в порядке, в каком их прислали
     * (кабель, гофра, … щит), добавленное руками — последним разделом,
     * свежее сверху, как было до разделов.
     *
     * @return Collection<int, array{title: string, items: Collection<int, ProjectProductSetItem>, total: float}>
     */
    public function sections(): Collection
    {
        $items = $this->items()->with('product.file')->orderBy('id')->get();

        return $items
            ->sortBy(fn (ProjectProductSetItem $i) => $i->section() === null ? [1, -$i->id] : [0, $i->id])
            ->groupBy(fn (ProjectProductSetItem $i) => $i->section() ?? '')
            ->map(fn (Collection $group, string $section) => [
                'title' => $section === '' ? 'Товары' : Str::ucfirst($section),
                'items' => $group->values(),
                'total' => round($group->sum(fn (ProjectProductSetItem $i) => $i->amount() ?? 0), 2),
            ])
            ->values();
    }

    /** Строки, не вошедшие в итог: без товара или с нулевой ценой. */
    public static function unpriced(Collection $sections): int
    {
        return $sections->flatMap(fn ($s) => $s['items'])->filter(fn (ProjectProductSetItem $i) => ! $i->unitPrice())->count();
    }
}
