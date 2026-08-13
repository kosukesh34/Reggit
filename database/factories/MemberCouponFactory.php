<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberCouponFactory extends Factory
{
    protected $model = MemberCoupon::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'member_id' => Member::factory(),
            'coupon_id' => Coupon::factory(),
            'coupon_name_snapshot' => null,
            'status' => MemberCoupon::STATUS_UNUSED,
            'issued_at' => now()->subDay(),
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(10),
            'used_at' => null,
            'order_id' => null,
            'issue_source_type' => null,
            'issue_source_id' => null,
            'issue_grant_key' => null,
        ];
    }
}
