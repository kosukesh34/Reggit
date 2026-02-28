<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;

class AdminMemberUpdateUseCase
{
    private const DEFAULT_POINT_DESCRIPTION = '管理画面での調整';

    private MemberAdminService $memberAdminService;

    public function __construct(MemberAdminService $memberAdminService)
    {
        $this->memberAdminService = $memberAdminService;
    }

    public function handle(AdminMemberUpdateInput $input): void
    {
        $member = $this->memberAdminService->findOrFail($input->memberId, $input->projectId);

        $this->memberAdminService->updateMemberProfile($member, $input->userData, $input->memberData);

        $adjustment = $input->pointAdjustment;
        if ($adjustment !== null) {
            $desc = isset($adjustment['description']) && (string) $adjustment['description'] !== ''
                ? (string) $adjustment['description']
                : self::DEFAULT_POINT_DESCRIPTION;
            $points = (int) $adjustment['points'];
            if ($adjustment['type'] === 'add') {
                $member->addPoints($points, $desc, null);
            } else {
                $member->usePoints($points, $desc, null);
            }
        }
    }
}
