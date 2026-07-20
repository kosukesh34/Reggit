<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Admin\ProjectProvisioningService;

class UpdateSmaregiLinkUseCase
{
    public function __construct(
        private ProjectProvisioningService $projectProvisioningService,
    ) {}

    public function handle(int $projectId, ?string $contractId): void
    {
        $this->projectProvisioningService->linkContract($projectId, $contractId);
    }
}
