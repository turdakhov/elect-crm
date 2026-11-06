<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CableJournalVersion extends Model
{
    protected $guarded = ['id'];

    public function casts(): array
    {
        return [
            'rows' => 'array',
            'changes' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function planSync(): BelongsTo
    {
        return $this->belongsTo(PlanSync::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
