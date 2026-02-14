<?php

namespace App\UseCases\Queue\Dto;

class QueueEntryDto
{
    public function __construct(
        public int $id,
        public int $queueNumber,
        public string $status,
        public int $partySize,
        public ?string $qrToken,
        public string $createdAt,
        public string $updatedAt,
        public ?string $calledAt,
        public int $waitingCount,
        public ?int $aheadCount,
        public int $estimatedWaitMinutes
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'queue_number' => $this->queueNumber,
            'status' => $this->status,
            'party_size' => $this->partySize,
            'qr_token' => $this->qrToken,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'called_at' => $this->calledAt,
            'waiting_count' => $this->waitingCount,
            'ahead_count' => $this->aheadCount,
            'estimated_wait_minutes' => $this->estimatedWaitMinutes,
        ];
    }
}
