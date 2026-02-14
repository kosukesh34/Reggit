<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'code' => strtoupper($this->faker->bothify('CPN###')),
            'name' => $this->faker->words(3, true),
            'image_url' => $this->faker->optional()->imageUrl(640, 480),
            'type' => 'amount',
            'value' => 100,
            'min_order_amount' => 0,
            'usage_limit' => 10,
            'used_count' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
            'is_active' => true,
        ];
    }
}
