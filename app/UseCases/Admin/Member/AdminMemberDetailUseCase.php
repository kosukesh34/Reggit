<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberDetailOutput;

class AdminMemberDetailUseCase
{
    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(int $projectId, int $memberId): AdminMemberDetailOutput
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $activeCoupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $activeStampCards = $this->memberAdminService->getActiveStampCardsForSelect($projectId);

        return new AdminMemberDetailOutput($member, $activeCoupons, $activeStampCards);
    }
}
