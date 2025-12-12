<?php

namespace Database\Seeders;

use App\Models\Complex;
use Illuminate\Database\Seeder;

class ComplexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Complex::factory()->count(10)->create();
    }
}
