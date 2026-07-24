<?php

namespace App\UseCases\Admin\Settings;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Services\Admin\LotteryAdminService;
use App\UseCases\Admin\Settings\Data\LotterySettingsView;

class GetLotterySettingsUseCase
{
    public function __construct(
        private LotteryAdminService $lotteryAdminService,
    ) {}

    public function handle(int $projectId): LotterySettingsView
    {
        $data = $this->lotteryAdminService->getSettings($projectId);

        return new LotterySettingsView(
            $data->settings,
            $data->prizes,
            $data->coupons,
            LotteryGrantTrigger::getValues(),
            LotteryRewardType::getValues(),
            $data->stats,
        );
    }
}
