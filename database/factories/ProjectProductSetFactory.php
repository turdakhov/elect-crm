<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectProductSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectProductSet>
 */
class ProjectProductSetFactory extends Factory
{
    protected $model = ProjectProductSet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory()->create()->id,
            'name' => $this->faker->word(),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}
