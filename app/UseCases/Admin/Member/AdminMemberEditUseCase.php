<?php

namespace App\UseCases\Admin\Member;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberEditOutput;

class AdminMemberEditUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, int $memberId): AdminMemberEditOutput
    {
        $member = $this->memberAdminService->findOrFail($memberId, $projectId);
        $rankOptions = $this->memberAdminService->getRankOptionsForSelect($projectId);
        $activeCoupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $activeStampCards = $this->memberAdminService->getActiveStampCardsForSelect($projectId);
        $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $customProfileFields = array_values($profileSettings['custom_fields'] ?? []);

        return new AdminMemberEditOutput(
            $member,
            $rankOptions,
            $activeCoupons,
            $activeStampCards,
            $customProfileFields,
        );
    }
}
