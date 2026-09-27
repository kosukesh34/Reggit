<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\QueueSettings;
use App\UseCases\Admin\Settings\Data\QueueSettingsView;

class GetQueueSettingsUseCase
{
    public function __construct(
        private QueueSettings $shopSettingRepository,
    ) {}

    public function handle(int $projectId): QueueSettingsView
    {
        return new QueueSettingsView(
            $this->shopSettingRepository->getQueueAvgServeMinutes($projectId),
            $this->shopSettingRepository->getQueueRequireMembership($projectId),
            $this->shopSettingRepository->getQueueChannelLine($projectId),
            $this->shopSettingRepository->getQueueChannelNative($projectId),
        );
    }
}
