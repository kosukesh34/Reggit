<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Domain\Settings\Repositories\ProfileSettings;
use App\Domain\Settings\Repositories\RankSettings;
use App\Models\MemberAudienceGroup;
use App\Models\Product;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberTagAdminService;
use App\Services\Admin\RfmAnalysisService;
use App\Services\WeatherCouponService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryFormView;

class AdminAfterRegistrationDeliveryCreateUseCase
{
    public function __construct(
        private CouponAdminService $couponAdminService,
        private RankSettings $shopSettingRepository,
        private MemberTagAdminService $memberTagAdminService,
        private RfmAnalysisService $rfmAnalysisService,
        private ProfileSettings $profileSettings,
        private WeatherCouponService $weatherCoupons,
    ) {}

    public function handle(int $projectId): AfterRegistrationDeliveryFormView
    {
        return new AfterRegistrationDeliveryFormView(
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            $this->rankOptions($projectId),
            null,
            Product::query()->where('project_id', $projectId)->orderBy('name')->get(['id', 'name']),
            $this->memberTagAdminService->getListByProject($projectId),
            $this->rfmAnalysisService->segmentOptions(),
            array_values($this->profileSettings->getProfileFieldSettings($projectId)['custom_fields'] ?? []),
            MemberAudienceGroup::query()->where('project_id', $projectId)->orderBy('name')->get(),
            $this->weatherCoupons->selectableStores($projectId),
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function rankOptions(int $projectId): array
    {
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

        return $rankOptions;
    }
}
