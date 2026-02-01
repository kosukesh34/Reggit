<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'member_number' => (string) $this->faker->unique()->numerify('##########'),
            'points' => 0,
            'status' => 'active',
            'birthday' => $this->faker->optional()->date(),
            'address' => $this->faker->optional()->address(),
        ];
    }
}
