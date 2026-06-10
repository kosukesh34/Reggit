<?php

namespace App\UseCases\Queue\Data;

use App\Models\User;

class QueueStoreInput
{
    public function __construct(
        public User $user,
        public int $partySize,
        public ?string $guestName,
        public ?string $guestPhone,
        public ?string $lineUserId,
    ) {}
}
