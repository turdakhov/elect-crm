<?php

namespace Database\Seeders;

use App\Models\PurchaseItem;
use Illuminate\Database\Seeder;

class PurchaseItemSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseItem::factory()->count(30)->create();
    }
}
