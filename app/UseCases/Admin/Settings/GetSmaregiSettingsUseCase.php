<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\SmaregiSettingsView;

class GetSmaregiSettingsUseCase
{
    public function handle(int $projectId): SmaregiSettingsView
    {
        $appUrl = rtrim(config('app.url'), '/');
        $idpUrl = $appUrl . '/api/smaregi/idp';
        $webhookUrl = $appUrl . '/api/smaregi/webhook';
        $linkedContractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
        $linkedContractId = is_string($linkedContractId) ? $linkedContractId : '';

        return new SmaregiSettingsView(
            $idpUrl,
            $webhookUrl,
            $appUrl,
            $linkedContractId,
        );
    }
}
