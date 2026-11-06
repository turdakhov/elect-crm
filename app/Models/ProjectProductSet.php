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
     * Смета из 3D-плана выдана закупщику и не правится: он по ней уже работает.
     * Изменение проекта приходит новой доп-сметой, а не правкой этой.
     */
    public function isLocked(): bool
    {
        return $this->plan_sync_id !== null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectProductSetItem::class);
    }
}
