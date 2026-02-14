<?php

namespace Database\Factories;

use App\Models\GalleryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryCategoryFactory extends Factory
{
    protected $model = GalleryCategory::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'name' => $this->faker->words(2, true),
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
