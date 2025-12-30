<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProductSetItem extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectProductSetItemFactory> */
    use HasFactory;

    protected $fillable = [
        'project_product_set_id',
        'product_id',
        'quantity',
    ];

    public function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function projectProductSet(): BelongsTo
    {
        return $this->belongsTo(ProjectProductSet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
