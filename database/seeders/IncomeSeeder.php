<?php

namespace Database\Seeders;

use App\Models\Income;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncomeSeeder extends Seeder
{
    public function run(): void
    {
        Income::factory()->count(20)->create();
    }
}
