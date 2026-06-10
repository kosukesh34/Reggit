<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

class MemberPointHistoryInput
{
    public function __construct(
        public User $user,
        public int $perPage = 20,
    ) {}
}
