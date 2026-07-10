<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;

/**
 * 順番待ち登録 UseCase への入力。検証済み。不変。
 */
class QueueStoreInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $partySize,
        public readonly ?string $guestName,
        public readonly ?string $guestPhone,
        public readonly ?string $lineUserId,
    ) {}
}
