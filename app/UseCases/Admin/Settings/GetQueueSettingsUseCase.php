<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;

class GetQueueSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /**
     * @return array{
     *     avgServeMinutes: int,
     *     requireMembership: bool,
     *     channelLine: bool,
     *     channelNative: bool
     * }
     */
    public function handle(int $projectId): array
    {
        return [
            'avgServeMinutes' => $this->shopSettingRepository->getQueueAvgServeMinutes($projectId),
            'requireMembership' => $this->shopSettingRepository->getQueueRequireMembership($projectId),
            'channelLine' => $this->shopSettingRepository->getQueueChannelLine($projectId),
            'channelNative' => $this->shopSettingRepository->getQueueChannelNative($projectId),
        ];
    }
}
