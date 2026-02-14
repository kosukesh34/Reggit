<?php

namespace Database\Factories;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryItemFactory extends Factory
{
    protected $model = GalleryItem::class;

    public function definition(): array
    {
        return [
            'project_id' => 1,
            'category_id' => GalleryCategory::factory(),
            'title' => $this->faker->sentence(3),
            'image_url' => $this->faker->imageUrl(640, 480),
            'description' => $this->faker->optional()->paragraph(),
            'size' => 'medium',
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
