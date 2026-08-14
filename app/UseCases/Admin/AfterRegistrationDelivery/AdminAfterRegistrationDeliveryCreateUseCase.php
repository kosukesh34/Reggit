<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryFormView;

class AdminAfterRegistrationDeliveryCreateUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): AfterRegistrationDeliveryFormView
    {
        return new AfterRegistrationDeliveryFormView(
            $this->memberAdminService->getActiveCouponsForSelect($projectId),
            $this->rankOptions($projectId),
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
