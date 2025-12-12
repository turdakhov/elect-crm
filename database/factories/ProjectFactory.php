<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Complex;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'client_id' => User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'complex_id' => Complex::query()->inRandomOrder()->value('id') ?? Complex::factory()->create()->id,
        ];
    }
}
