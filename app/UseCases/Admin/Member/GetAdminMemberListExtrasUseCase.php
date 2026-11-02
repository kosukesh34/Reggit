<?php

namespace App\UseCases\Admin\Member;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Models\MemberAudienceGroup;
use App\Models\Product;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberTagAdminService;
use App\Services\Admin\RfmAnalysisService;
use App\UseCases\Admin\Member\Data\AdminMemberListExtras;
use Illuminate\Support\Collection;

class GetAdminMemberListExtrasUseCase
{
    public function __construct(
        private ProfileSettings $profileSettings,
        private CouponAdminService $couponAdminService,
        private MemberTagAdminService $memberTagAdminService,
        private RfmAnalysisService $rfmAnalysisService,
    ) {}

    /** @param  bool  $withAudienceGroups  配信グループの作成では、グループの中にグループは入れられないため false */
    public function handle(int $projectId, bool $withAudienceGroups = true): AdminMemberListExtras
    {
        return new AdminMemberListExtras(
            $this->memberTagAdminService->getListByProject($projectId),
            Product::query()->where('project_id', $projectId)->orderBy('name')->get(['id', 'name']),
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            $this->rfmAnalysisService->segmentOptions(),
            array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            $withAudienceGroups
                ? MemberAudienceGroup::query()->where('project_id', $projectId)->orderBy('name')->get()
                : new Collection,
        );
    }
}
