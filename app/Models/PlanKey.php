<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Родовое имя из 3D-плана → конкретный товар. Сменили бренд — поменяли
 * товар здесь; выданные сметы при этом не меняются, а разница по ключам
 * остаётся нулевой: проект-то не менялся.
 */
class PlanKey extends Model
{
    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
