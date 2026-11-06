<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Отправка из 3D-плана: полный журнал и полная потребность на момент нажатия
 * кнопки. Разницу с уже принятым и выданным считает CRM — только она знает,
 * что ушло закупщику.
 */
class PlanSync extends Model
{
    protected $guarded = ['id'];

    public function casts(): array
    {
        return [
            'journal' => 'array',
            'needs' => 'array',
            'warnings' => 'array',
            'journal_accepted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function journalAcceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journal_accepted_by');
    }

    public function productSets(): HasMany
    {
        return $this->hasMany(ProjectProductSet::class);
    }

    /**
     * Действовать можно только по последней отправке проекта: принять старую
     * значило бы откатить журнал или выдать смету по устаревшей модели.
     */
    public function isLatest(): bool
    {
        return ! static::where('project_id', $this->project_id)->where('id', '>', $this->id)->exists();
    }
}
