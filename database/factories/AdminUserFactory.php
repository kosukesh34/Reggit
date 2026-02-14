<?php

namespace Database\Factories;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'login_id' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password',
            'name' => $this->faker->name(),
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => Project::factory(),
            'created_by_id' => null,
        ];
    }
}
