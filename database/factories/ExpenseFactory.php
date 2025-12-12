<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory()->create()->id,
            'given_to' => User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'expense_type_id' => \App\Models\ExpenseType::query()->inRandomOrder()->value('id') ?? \App\Models\ExpenseType::factory()->create()->id,
            'amount' => $this->faker->numberBetween(1000, 5_000_000),
            'description' => $this->faker->optional()->sentence(),
            'given_at' => $this->faker->date(),
        ];
    }
}
