<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        Expense::factory()->count(20)->create();
    }
}
