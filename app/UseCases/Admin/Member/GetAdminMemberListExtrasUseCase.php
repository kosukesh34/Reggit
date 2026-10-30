<?php

namespace App\UseCases\Admin\Member;

use App\Domain\Settings\Repositories\FrontendShellSettings;
use App\Domain\Settings\Repositories\ProfileSettings;
use App\Models\MemberAudienceGroup;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberTagAdminService;
use App\Services\Admin\RfmAnalysisService;
use App\UseCases\Admin\Member\Data\AdminMemberListExtras;

class GetAdminMemberListExtrasUseCase
{
    public function __construct(
        private FrontendShellSettings $shopSettingRepository,
        private ProfileSettings $profileSettings,
        private CouponAdminService $couponAdminService,
        private MemberTagAdminService $memberTagAdminService,
        private RfmAnalysisService $rfmAnalysisService,
    ) {}

    public function handle(int $projectId): AdminMemberListExtras
    {
        [$inviteUrl, $inviteLabel] = $this->lineInvite($projectId);

        return new AdminMemberListExtras(
            $inviteUrl,
            $inviteLabel,
            $this->memberTagAdminService->getListByProject($projectId),
            Product::query()->where('project_id', $projectId)->orderBy('name')->get(['id', 'name']),
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            $this->rfmAnalysisService->segmentOptions(),
            array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            MemberAudienceGroup::query()->where('project_id', $projectId)->orderBy('name')->get(),
        );
    }

    /** @return array{0: string, 1: string} */
    private function lineInvite(int $projectId): array
    {
        $friendAddUrl = trim($this->shopSettingRepository->getLineFriendAddUrl($projectId));
        if ($friendAddUrl !== '') {
            return [$friendAddUrl, 'LINE友だち追加URL'];
        }

        $liffUrl = trim($this->shopSettingRepository->getLineLiffUrl($projectId));
        if ($liffUrl !== '') {
            return [$liffUrl, 'LIFF URL'];
        }

        $projectKey = Project::query()->where('id', $projectId)->value('project_key');
        $fallback = is_string($projectKey) && $projectKey !== ''
            ? url('/liff/'.$projectKey)
            : url('/liff');

        return [$fallback, 'LIFFエントリURL'];
    }
}
