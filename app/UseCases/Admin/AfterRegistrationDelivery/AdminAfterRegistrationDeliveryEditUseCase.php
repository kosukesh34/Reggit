<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\AfterRegistrationDeliveryAdminService;
use App\Services\Admin\CouponAdminService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryFormView;

class AdminAfterRegistrationDeliveryEditUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service,
        private CouponAdminService $couponAdminService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, int $id): AfterRegistrationDeliveryFormView
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

        return new AfterRegistrationDeliveryFormView(
            $this->couponAdminService->getActiveCouponsForSelect($projectId),
            $rankOptions,
            $this->service->findOrFail($projectId, $id),
        );
    }
}
