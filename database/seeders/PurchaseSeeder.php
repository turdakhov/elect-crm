<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        Purchase::factory()->count(15)->create();
    }
}
