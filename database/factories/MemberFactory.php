<?php

namespace Database\Factories;

use App\Enums\MemberNotificationPreference;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'user_id' => User::factory(),
            'member_number' => (string) $this->faker->unique()->numerify('##########'),
            'points' => 0,
            'status' => 'active',
            'notification_preference' => MemberNotificationPreference::ALL,
            'birthday' => $this->faker->optional()->date(),
            'address' => $this->faker->optional()->address(),
        ];
    }
}
