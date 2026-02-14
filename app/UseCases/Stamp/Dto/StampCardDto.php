<?php

namespace App\UseCases\Stamp\Dto;

class StampCardDto
{
    public function __construct(
        public int $id,
        public string $name,
        public int $requiredStamps,
        public ?string $rewardDescription,
        public ?string $imageUrl,
        public int $currentStamps,
        public ?string $completedAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'required_stamps' => $this->requiredStamps,
            'reward_description' => $this->rewardDescription,
            'image_url' => $this->imageUrl,
            'current_stamps' => $this->currentStamps,
            'completed_at' => $this->completedAt,
        ];
    }
}
