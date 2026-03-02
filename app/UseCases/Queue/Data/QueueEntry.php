<?php

namespace App\UseCases\Queue\Data;

/** 待ち行列エントリ1件の表示用データ。不変。 */
class QueueEntry
{
    public function __construct(
        public int $id,
        public int $queueNumber,
        public string $status,
        public int $partySize,
        public bool $lineLinked,
        public ?string $qrToken,
        public string $createdAt,
        public string $updatedAt,
        public ?string $calledAt,
        public int $waitingCount,
        public ?int $aheadCount,
        public int $estimatedWaitMinutes
    ) {}

    /** @return array{id: int, queue_number: int, status: string, party_size: int, line_linked: bool, qr_token: string|null, created_at: string, updated_at: string, called_at: string|null, waiting_count: int, ahead_count: int|null, estimated_wait_minutes: int} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'queue_number' => $this->queueNumber,
            'status' => $this->status,
            'party_size' => $this->partySize,
            'line_linked' => $this->lineLinked,
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
