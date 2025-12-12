<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory()->create()->id,
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'purchased_at' => $this->faker->date(),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
