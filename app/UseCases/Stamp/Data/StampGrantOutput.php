<?php

namespace App\UseCases\Stamp\Data;

class StampGrantOutput
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly int $status,
        public readonly string $message,
        public readonly ?int $stampCardId = null,
        public readonly ?int $currentStamps = null,
        public readonly int $grantedCount = 0,
        public readonly bool $completedNow = false,
        public readonly int $cycleCount = 0,
        public readonly ?int $nextStampCardId = null,
        public readonly ?string $nextStampCardName = null,
    ) {}

    /**
     * @return array{
     *     message: string,
     *     stamp_card_id: int|null,
     *     current_stamps: int|null,
     *     granted_count: int,
     *     completed_now: bool,
     *     cycle_count: int,
     *     next_stamp_card_id: int|null,
     *     next_stamp_card_name: string|null
     * }
     */
    public function successPayload(): array
    {
        return [
            'message' => $this->message,
            'stamp_card_id' => $this->stampCardId,
            'current_stamps' => $this->currentStamps,
            'granted_count' => $this->grantedCount,
            'completed_now' => $this->completedNow,
            'cycle_count' => $this->cycleCount,
            'next_stamp_card_id' => $this->nextStampCardId,
            'next_stamp_card_name' => $this->nextStampCardName,
        ];
    }
}
