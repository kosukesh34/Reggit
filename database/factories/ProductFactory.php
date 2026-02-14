<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'price' => $this->faker->randomFloat(0, 100, 2000),
            'image_url' => $this->faker->optional()->imageUrl(200, 200),
            'category' => 'other',
            'is_available' => true,
            'stock' => $this->faker->numberBetween(5, 20),
        ];
    }
}
