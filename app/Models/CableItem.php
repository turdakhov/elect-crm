<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CableItem extends Model
{
    /** @use HasFactory<\Database\Factories\CableItemFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'floor',
        'room',
        'name',
        'code',
        'comment',
        'cable_id',
        'cable_count',
        'pipe_id',
        'cable_length',
        'pipe_length',
    ];

    public function casts(): array
    {
        return [
            'cable_count' => 'integer',
            'cable_length' => 'float',
            'pipe_length' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cable(): BelongsTo
    {
        return $this->belongsTo(Cable::class);
    }

    public function pipe(): BelongsTo
    {
        return $this->belongsTo(Pipe::class);
    }
}
