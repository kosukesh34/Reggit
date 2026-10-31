<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\Member\Data\IssuedTransferCode;
use App\Services\Member\MemberTransferCodeIssuer;

class IssueMemberTransferCodeUseCase
{
    public function __construct(private MemberTransferCodeIssuer $issuer) {}

    public function handle(User $user, ?int $adminUserId): IssuedTransferCode
    {
        return $this->issuer->issue($user, $adminUserId);
    }
}
