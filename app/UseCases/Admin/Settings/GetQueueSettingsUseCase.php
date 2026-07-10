<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class GetQueueSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): array
    {
        $avgServeMinutes = $this->shopSettingRepository->getQueueAvgServeMinutes($projectId);

        return ['avgServeMinutes' => $avgServeMinutes];
    }
}
