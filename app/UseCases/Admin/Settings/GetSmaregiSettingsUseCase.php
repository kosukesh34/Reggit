<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Admin\Settings\Data\SmaregiSettingsView;

class GetSmaregiSettingsUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    public function handle(int $projectId): SmaregiSettingsView
    {
        $appUrl = rtrim(config('app.url'), '/');
        $idpUrl = $appUrl.'/api/smaregi/idp';
        $webhookUrl = $appUrl.'/api/smaregi/webhook';
        $linkedContractId = $this->smaregiApi->getContractIdForProject($projectId);
        $linkedContractId = is_string($linkedContractId) ? trim($linkedContractId) : '';
        $storeId = trim((string) ShopSetting::getValue('smaregi_store_id', '', $projectId));
        $terminalId = trim((string) ShopSetting::getValue('smaregi_terminal_id', '', $projectId));

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
