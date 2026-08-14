<?php

namespace Database\Factories;

use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
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
            'image_fit' => Coupon::IMAGE_FIT_COVER,
            'type' => 'amount',
            'value' => 100,
            'min_order_amount' => 0,
            'max_order_amount' => null,
            'apply_condition_division' => CouponApplyConditionDivision::ALL,
            'combine_division' => CouponCombineDivision::ALLOWED,
            'usage_limit' => 10,
            'used_count' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDays(10),
            'is_active' => true,
        ];
    }
}
