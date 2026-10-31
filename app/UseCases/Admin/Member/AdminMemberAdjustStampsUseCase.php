<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberStampAdjuster;

class AdminMemberAdjustStampsUseCase
{
    public function __construct(private MemberStampAdjuster $stamps) {}

    public function handle(int $projectId, int $memberId, int $stampCardId, int $add, int $subtract, ?string $description = null): void
    {
        $this->stamps->adjust($projectId, $memberId, $stampCardId, $add, $subtract, $description);
    }
}
