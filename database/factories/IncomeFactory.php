<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory()->create()->id,
            'given_by' => User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'amount' => $this->faker->numberBetween(1000, 5_000_000),
            'description' => $this->faker->optional()->sentence(),
            'received_at' => $this->faker->date(),
            'contract_number' => $this->faker->optional()->bothify('CN-####'),
        ];
    }
}
