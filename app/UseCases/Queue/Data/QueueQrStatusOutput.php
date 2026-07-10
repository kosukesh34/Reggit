<?php

namespace App\UseCases\Queue\Data;

/** QR 連携ステータス UseCase の出力。不変。 */
class QueueQrStatusOutput
{
    public function __construct(
        public readonly QueueEntry $queueEntry,
        public readonly int $sessionEntryId,
        public readonly int $sessionProjectId,
    ) {}
}
