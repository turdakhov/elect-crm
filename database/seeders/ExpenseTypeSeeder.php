<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $expenseTypes = [
            'Зарплаты',
            'Личные доли',
            'Реклама',
            'Инструменты',
            'Рабочие расходы',
            'Услуги',
        ];

        foreach ($expenseTypes as $name) {
            ExpenseType::firstOrCreate(['name' => $name]);
        }
    }
}
