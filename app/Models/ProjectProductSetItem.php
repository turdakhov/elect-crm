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
        'plan_key',
        'quantity',
        'comment',
    ];

    public function casts(): array
    {
        return [
            'quantity' => 'float',
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

    /**
     * Цена берётся из справочника на сегодня, в строке её нет: смета — это
     * прикидка закупа, и подорожавший товар должен подорожать и в ней.
     * Без товара цены нет — такая строка в итог не входит.
     */
    public function unitPrice(): ?float
    {
        return $this->product ? (float) $this->product->approximate_price : null;
    }

    public function amount(): ?float
    {
        $price = $this->unitPrice();

        return $price === null ? null : round($this->quantity * $price, 2);
    }

    /** Раздел 3D-плана — то, что в ключе до «|»: «кабель», «щит: аппараты». */
    public function section(): ?string
    {
        if (! $this->plan_key || ! str_contains($this->plan_key, '|')) {
            return null;
        }

        return explode('|', $this->plan_key, 2)[0];
    }
}
