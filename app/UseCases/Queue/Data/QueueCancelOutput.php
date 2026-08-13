<?php

namespace App\UseCases\Queue\Data;

use App\Models\QueueEntry;

class QueueCancelOutput
{
    public function __construct(
        public readonly ?QueueEntry $entry,
        public readonly bool $isConflict,
    ) {}
}
