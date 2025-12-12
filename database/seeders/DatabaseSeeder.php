<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in dependency order: Users -> FileTypes -> ExpenseTypes -> Complexes -> Projects -> Products -> Purchases -> PurchaseItems -> Incomes/Expenses
        $this->call([
            UserSeeder::class,
            FileTypeSeeder::class,
            ExpenseTypeSeeder::class,
            ComplexSeeder::class,
            ProjectSeeder::class,
            ProductSeeder::class,
            PurchaseSeeder::class,
            PurchaseItemSeeder::class,
            IncomeSeeder::class,
            ExpenseSeeder::class,
        ]);
    }
}
