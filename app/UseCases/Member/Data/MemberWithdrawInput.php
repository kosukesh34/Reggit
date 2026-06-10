<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

class MemberWithdrawInput
{
    public function __construct(
        public User $user,
    ) {}
}
