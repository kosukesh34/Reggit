<?php

namespace App\UseCases\Queue\Data;

/** 「自分の待ち状況」UseCase の実行結果。不変。 */
class QueueMeResult
{
    public function __construct(
        public ?QueueEntry $queueEntry,
        public int $waitingCount,
        public int $estimatedWaitMinutes
    ) {
    }
}
