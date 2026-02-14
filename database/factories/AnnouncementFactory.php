<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'title' => $this->faker->sentence(6),
            'body' => $this->faker->paragraph(),
            'published_at' => now()->subDay(),
            'expires_at' => null,
            'is_pinned' => false,
        ];
    }
}
