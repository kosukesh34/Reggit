<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

class MemberUserInput
{
    public function __construct(
        public User $user,
    ) {}
}
