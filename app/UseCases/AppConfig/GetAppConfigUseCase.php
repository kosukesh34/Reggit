<?php

namespace App\UseCases\AppConfig;

use App\Services\AppConfigService;
use App\UseCases\AppConfig\Data\AppConfigView;

class GetAppConfigUseCase
{
    public function __construct(
        private AppConfigService $appConfigService,
    ) {}

    public function handle(string $projectKey, bool $liff = false): AppConfigView
    {
        return new AppConfigView(
            $this->appConfigService->buildForProjectKey($projectKey, $liff),
        );
    }
}
