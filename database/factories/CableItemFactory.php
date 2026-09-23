<?php

namespace Database\Factories;

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\Pipe;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CableItem>
 */
class CableItemFactory extends Factory
{
    protected $model = CableItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id') ?? Project::factory()->create()->id,
            'floor' => $this->faker->optional()->randomElement(['1', '2', '3', 'Подвал', 'Чердак']),
            'room' => $this->faker->randomElement(['Гостиная', 'Спальня', 'Кухня', 'Ванная', 'Коридор']),
            'name' => $this->faker->words(3, true),
            'comment' => $this->faker->optional()->sentence(),
            'cable_id' => Cable::query()->inRandomOrder()->value('id') ?? Cable::factory()->create()->id,
            'cable_count' => 1,
            'pipe_id' => Pipe::query()->inRandomOrder()->value('id') ?? Pipe::factory()->create()->id,
            'cable_length' => $this->faker->randomFloat(2, 1, 100),
            'pipe_length' => $this->faker->randomFloat(2, 1, 100),
        ];
    }
}
