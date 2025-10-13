<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function givenTo()
    {
        return $this->belongsTo(User::class, 'given_to');
    }
}
