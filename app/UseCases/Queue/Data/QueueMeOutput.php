<?php

namespace App\UseCases\Queue\Data;

/** 「自分の待ち状況」UseCase の出力。不変。 */
class QueueMeOutput
{
    public function __construct(
        public ?QueueEntry $queueEntry,
        public int $waitingCount,
        public int $estimatedWaitMinutes,
        public ?int $sessionEntryIdToRemember = null,
        public bool $shouldForgetSession = false,
        public ?int $sessionProjectId = null,
    ) {}

    /**
     * @return array{
     *     queue_entry: array{id: int, queue_number: int, formatted_queue_number: string, status: string, party_size: int, line_linked: bool, link_channel: string, qr_token: string|null, created_at: string, updated_at: string, called_at: string|null, waiting_count: int, ahead_count: int|null, estimated_wait_minutes: int}|null,
     *     updated_at: string,
     *     message?: string,
     *     waiting_count?: int,
     *     estimated_wait_minutes?: int
     * }
     */
    public function toArray(): array
    {
        if ($this->queueEntry === null) {
            return [
                'queue_entry' => null,
                'message' => '待ち番号はありません',
                'waiting_count' => $this->waitingCount,
                'estimated_wait_minutes' => $this->estimatedWaitMinutes,
                'updated_at' => now()->toIso8601String(),
            ];
        }

        return [
            'queue_entry' => $this->queueEntry->toArray(),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
