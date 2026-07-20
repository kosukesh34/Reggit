<?php

namespace App\UseCases\Stamp\Data;

/** スタンプカード1枚の表示用データ。不変。（Model の StampCard と区別するため View を付与） */
class StampCardView
{
    public function __construct(
        public int $id,
        public string $name,
        public int $requiredStamps,
        public ?string $rewardDescription,
        /** @var array<int, array{stamp_count: int, description: string}> */
        public array $rewardMilestones,
        public ?string $imageUrl,
        public int $currentStamps,
        public ?string $completedAt,
        public string $layoutStyle = 'grid',
        public ?int $validDays = null,
        public ?string $validUntil = null,
        public ?string $memberStampValidUntil = null,
        /** @var array<string, string> */
        public array $stampImages = []
    ) {}

    /** @return array{id: int, name: string, required_stamps: int, reward_description: string|null, reward_milestones: array<int, array{stamp_count: int, description: string}>, image_url: string|null, current_stamps: int, completed_at: string|null, layout_style: string, valid_days: int|null, valid_until: string|null, member_stamp_valid_until: string|null, stamp_images: array<string, string>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'required_stamps' => $this->requiredStamps,
            'reward_description' => $this->rewardDescription,
            'reward_milestones' => $this->rewardMilestones,
            'image_url' => $this->imageUrl,
            'current_stamps' => $this->currentStamps,
            'completed_at' => $this->completedAt,
            'layout_style' => $this->layoutStyle,
            'valid_days' => $this->validDays,
            'valid_until' => $this->validUntil,
            'member_stamp_valid_until' => $this->memberStampValidUntil,
            'stamp_images' => $this->stampImages,
        ];
    }
}
