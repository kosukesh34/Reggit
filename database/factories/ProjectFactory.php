<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_key' => $this->faker->unique()->slug(2),
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
