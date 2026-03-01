<?php

namespace App\UseCases\Stamp\Data;

/** スタンプ一覧画面用の表示データ。 @param array<int, StampCardView> $stampCards */
class StampListView
{
    public function __construct(
        public array $stampCards,
        public ?string $defaultImageUrl,
        public bool $requiresMembership
    ) {}

    /** @return array<int, array{id: int, name: string, required_stamps: int, reward_description: string|null, reward_milestones: array<int, array{stamp_count: int, description: string}>, image_url: string|null, current_stamps: int, completed_at: string|null}> */
    public function stampCardsToArray(): array
    {
        $payload = [];
        foreach ($this->stampCards as $card) {
            $payload[] = $card->toArray();
        }

        return $payload;
    }
}
