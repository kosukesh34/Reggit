<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;

/**
 * 「自分の待ち状況」UseCase への入力。不変。
 *
 * @param  list<string|null>  $embeddedRawParams
 */
class QueueMeInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $projectId,
        public readonly string $lineUserId,
        public readonly ?string $queueToken,
        public readonly ?int $queueId,
        public readonly ?int $sessionEntryId,
        public readonly array $embeddedRawParams,
    ) {}
}
