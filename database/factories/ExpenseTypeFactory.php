<?php

namespace Database\Factories;

use App\Models\ExpenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseType>
 */
class ExpenseTypeFactory extends Factory
{
    protected $model = ExpenseType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Материалы', 'Инструменты', 'Транспорт', 'Услуги', 'Прочее',
            ]),
        ];
    }
}
