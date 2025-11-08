<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function givenBy()
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
