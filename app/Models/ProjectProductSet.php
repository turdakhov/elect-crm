<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
