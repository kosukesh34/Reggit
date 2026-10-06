<?php

namespace App\UseCases\Admin\Coupon;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberAudienceGroup;
use App\Models\Product;
use App\Models\StampCard;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberTagAdminService;
use App\Services\Admin\RfmAnalysisService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\UseCases\Admin\Coupon\Data\AdminCouponBulkIssueFormOutput;

class AdminCouponBulkIssueFormUseCase
{
    public function __construct(
        private CouponAdminService $couponAdminService,
        private SmaregiCouponSyncService $smaregiCouponSync,
        private RankSettings $shopSettingRepository,
        private RfmAnalysisService $rfmAnalysisService,
        private MemberTagAdminService $memberTagAdminService,
        private ProfileSettings $profileSettings,
    ) {}

    public function handle(int $projectId): AdminCouponBulkIssueFormOutput
    {
        $coupons = $this->couponAdminService->getActiveCouponsForSelect($projectId);

        $activeCount = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->count();
        $linkedCount = Coupon::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->whereNotNull('smaregi_coupon_id')
            ->where('smaregi_coupon_id', '!=', '')
            ->count();

        $rankOptions = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if (($rank['is_active'] ?? true) !== true) {
                continue;
            }
            $rankOptions[] = [
                'key' => (string) $rank['key'],
                'label' => (string) ($rank['label'] ?? $rank['key']),
            ];
        }

        $products = Product::query()
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get(['id', 'name', 'smaregi_product_id']);

        $stampCards = StampCard::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'required_stamps']);

        return new AdminCouponBulkIssueFormOutput(
            $coupons,
            Member::where('project_id', $projectId)->count(),
            $this->smaregiCouponSync->hasSmaregiContract($projectId),
            $linkedCount,
            $activeCount - $linkedCount,
            deliveryTypeOptions: CouponDeliveryType::getValues(),
            deliveryGroups: CouponDeliveryType::formGroups(),
            deliveryTypeJsConfig: CouponDeliveryType::jsConfig(),
            products: $products,
            stampCards: $stampCards,
            rankOptions: $rankOptions,
            defaultDeliveryType: CouponDeliveryType::IMMEDIATE_ALL,
            rfmSegments: $this->rfmAnalysisService->segmentOptions(),
            memberTags: $this->memberTagAdminService->getListByProject($projectId),
            profileFields: array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            audienceGroups: MemberAudienceGroup::query()->where('project_id', $projectId)->orderBy('name')->get(),
        );
    }
}
