<?php

namespace App\UseCases\Stamp\Dto;

class StampListDto
{
    /** @param array<int, StampCardDto> $stampCards */
    public function __construct(
        public array $stampCards,
        public ?string $defaultImageUrl,
        public bool $requiresMembership
    ) {
    }

    public function stampCardsToArray(): array
    {
        $payload = [];
        foreach ($this->stampCards as $card) {
            $payload[] = $card->toArray();
        }
        return $payload;
    }
}
