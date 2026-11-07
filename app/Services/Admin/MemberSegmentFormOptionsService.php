<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Domain\Settings\Repositories\RankSettings;
use App\Models\MemberAudienceGroup;
use App\Models\Product;
use App\Services\Admin\Data\MemberSegmentFormOptions;
use Illuminate\Support\Collection;

class MemberSegmentFormOptionsService
{
    public function __construct(
        private CouponAdminService $couponAdminService,
        private MemberTagAdminService $memberTagAdminService,
        private RfmAnalysisService $rfmAnalysisService,
        private ProfileSettings $profileSettings,
        private RankSettings $rankSettings,
    ) {}

    public function forProject(int $projectId, bool $withAudienceGroups = true): MemberSegmentFormOptions
    {
        return new MemberSegmentFormOptions(
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            Product::query()->where('project_id', $projectId)->orderBy('name')->get(['id', 'name', 'smaregi_product_id']),
            $this->memberTagAdminService->getListByProject($projectId),
            $this->rfmAnalysisService->segmentOptions(),
            array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            $withAudienceGroups
                ? MemberAudienceGroup::query()->where('project_id', $projectId)->orderBy('name')->get()
                : new Collection,
            $this->activeRankOptions($projectId),
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function activeRankOptions(int $projectId): array
    {
        $rankOptions = [];
        foreach ($this->rankSettings->getMemberRankSettings($projectId) as $rank) {
            if (($rank['is_active'] ?? true) !== true) {
                continue;
            }
            $rankOptions[] = [
                'key' => (string) $rank['key'],
                'label' => (string) ($rank['label'] ?? $rank['key']),
            ];
        }

        return $rankOptions;
    }
}
