<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Admin\ProjectProvisioningService;

class UpdateSmaregiLinkUseCase
{
    public function __construct(
        private ProjectProvisioningService $projectProvisioningService,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(
        int $projectId,
        ?string $contractId,
        ?string $storeId = null,
        ?string $terminalId = null,
    ): void {
        $this->projectProvisioningService->linkContract($projectId, $contractId);
        if ($storeId !== null) {
            $this->shopSettingRepository->setValue('smaregi_store_id', trim($storeId), 'string', $projectId);
        }
        if ($terminalId !== null) {
            $this->shopSettingRepository->setValue('smaregi_terminal_id', trim($terminalId), 'string', $projectId);
        }
    }
}
