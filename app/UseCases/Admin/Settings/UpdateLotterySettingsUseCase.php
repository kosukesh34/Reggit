<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Services\Admin\LotteryAdminService;

class UpdateLotterySettingsUseCase
{
    public function __construct(
        private LotteryAdminService $lotteryAdminService,
        private FeatureSettings $shopSettingRepository,
    ) {}

    /**
     * @param  array{
     *     gacha_enabled: bool,
     *     scratch_enabled: bool,
     *     roulette_enabled: bool,
     *     grant_trigger: string,
     *     min_amount: float|null,
     *     entitlement_expires_hours: int|null,
     *     prizes: list<array{reward_type: string, coupon_id: int|null, is_lose: bool, probability: int, sort_order?: int}>
     * }  $validated
     */
    public function handle(int $projectId, array $validated): void
    {
        $this->lotteryAdminService->updateSettings($projectId, $validated);

        $flags = $this->shopSettingRepository->getFeatureFlags($projectId);
        $flags['feature_gacha_enabled'] = (bool) $validated['gacha_enabled'];
        $flags['feature_scratch_enabled'] = (bool) $validated['scratch_enabled'];
        $flags['feature_roulette_enabled'] = (bool) $validated['roulette_enabled'];
        $this->shopSettingRepository->setFeatureFlags($flags, $projectId);
    }
}
