<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Admin\Settings\Data\SmaregiSettingsView;

class GetSmaregiSettingsUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): SmaregiSettingsView
    {
        $appUrl = rtrim(config('app.url'), '/');
        $idpUrl = $appUrl.'/api/smaregi/idp';
        $webhookUrl = $appUrl.'/api/smaregi/webhook';
        $linkedContractId = $this->smaregiApi->getContractIdForProject($projectId);
        $linkedContractId = is_string($linkedContractId) ? trim($linkedContractId) : '';
        $storeId = trim((string) $this->shopSettingRepository->getValue('smaregi_store_id', '', $projectId));
        $terminalId = trim((string) $this->shopSettingRepository->getValue('smaregi_terminal_id', '', $projectId));

        return new SmaregiSettingsView(
            $idpUrl,
            $webhookUrl,
            $appUrl,
            $linkedContractId,
            $storeId,
            $terminalId,
        );
    }
}
