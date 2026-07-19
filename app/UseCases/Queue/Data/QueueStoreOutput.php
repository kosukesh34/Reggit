<?php

namespace App\UseCases\Queue\Data;

/** 順番待ち登録 UseCase の出力。不変。 */
class QueueStoreOutput
{
    public function __construct(
        public bool $requiresMembership,
        public ?QueueEntry $entry,
    ) {}
}
