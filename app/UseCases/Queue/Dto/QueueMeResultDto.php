<?php

namespace App\UseCases\Queue\Dto;

class QueueMeResultDto
{
    public function __construct(
        public ?QueueEntryDto $queueEntry,
        public int $waitingCount,
        public int $estimatedWaitMinutes
    ) {
    }
}
