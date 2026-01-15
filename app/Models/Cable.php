<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cable extends Model
{
    /** @use HasFactory<\Database\Factories\CableFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
