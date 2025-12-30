<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectProductSetItem>
 */
class ProjectProductSetItemFactory extends Factory
{
    protected $model = ProjectProductSetItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_product_set_id' => ProjectProductSet::query()->inRandomOrder()->value('id') ?? ProjectProductSet::factory()->create()->id,
            'product_id' => Product::query()->inRandomOrder()->value('id') ?? Product::factory()->create()->id,
            'quantity' => $this->faker->numberBetween(1, 50),
        ];
    }
}
