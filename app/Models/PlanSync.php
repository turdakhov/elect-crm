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

    public const TRACE_SCOPE = 'трассы';

    /**
     * Действовать можно только по последней отправке своей части: принять
     * старую значило бы откатить журнал или выдать смету по устаревшей модели.
     * Отправка щита трассы не устаревает — это другая смета.
     */
    public function isLatest(): bool
    {
        return ! static::where('project_id', $this->project_id)->where('scope', $this->scope)
            ->where('id', '>', $this->id)->exists();
    }

    /** Журнал есть только у трасс: у щита кабелей с наклейками нет. */
    public function hasJournal(): bool
    {
        return $this->scope === self::TRACE_SCOPE;
    }

    public function scopeLabel(): string
    {
        return $this->scope_name ?: $this->scope;
    }
}
