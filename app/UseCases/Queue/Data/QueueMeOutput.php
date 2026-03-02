<?php

namespace App\UseCases\Queue\Data;

/** 「自分の待ち状況」UseCase の出力。不変。 */
class QueueMeOutput
{
    public function __construct(
        public ?QueueEntry $queueEntry,
        public int $waitingCount,
        public int $estimatedWaitMinutes
    ) {}
}
