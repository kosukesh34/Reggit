<?php

namespace Database\Factories;

use App\Enums\StampCycleMode;
use App\Models\StampCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCardFactory extends Factory
{
    protected $model = StampCard::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'name' => $this->faker->words(2, true),
            'required_stamps' => 10,
            'reward_description' => $this->faker->sentence(),
            'reward_milestones' => null,
            'image_url' => $this->faker->optional()->imageUrl(640, 480),
            'is_active' => true,
            'cycle_mode' => StampCycleMode::REPEAT,
        ];
    }
}
