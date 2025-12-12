<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::query()->inRandomOrder()->value('id') ?? Purchase::factory()->create()->id,
            'product_id' => Product::query()->inRandomOrder()->value('id') ?? Product::factory()->create()->id,
            'quantity' => $this->faker->numberBetween(1, 50),
            'unit_price' => $this->faker->numberBetween(10000, 5_000_000),
        ];
    }
}
