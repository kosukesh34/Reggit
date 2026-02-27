<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Admin\Settings\Data\SmaregiSettingsView;

class GetSmaregiSettingsUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {
    }

    public function handle(int $projectId): SmaregiSettingsView
    {
        $appUrl = rtrim(config('app.url'), '/');
        $idpUrl = $appUrl . '/api/smaregi/idp';
        $webhookUrl = $appUrl . '/api/smaregi/webhook';
        $linkedContractId = ShopSetting::getValue('smaregi_contract_id', null, $projectId);
        $linkedContractId = is_string($linkedContractId) ? trim($linkedContractId) : '';

        // 開発用: 接続・POS状態を表示（後で削除可）
        $posStatusMessage = null;
        $posStatusEnvCheck = null;
        if ($linkedContractId !== '') {
            $token = $this->smaregiApi->getAppAccessToken($linkedContractId);
            if ($token === null) {
                $posStatusMessage = '接続済み・トークン取得失敗（.env の SMAREGI_CLIENT_ID / SMAREGI_CLIENT_SECRET を確認。詳細は storage/logs/laravel.log の [Smaregi]）';
                $clientId = config('smaregi.client_id');
                $clientSecret = config('smaregi.client_secret');
                $posStatusEnvCheck = 'SMAREGI_CLIENT_ID: ' . (is_string($clientId) && trim($clientId) !== '' ? '設定済み' : '未設定')
                    . ' / SMAREGI_CLIENT_SECRET: ' . (is_string($clientSecret) && trim($clientSecret) !== '' ? '設定済み' : '未設定');
            } else {
                $storesResult = $this->smaregiApi->getStores($linkedContractId);
                if ($storesResult === null) {
                    $posStatusMessage = '接続済み・店舗API失敗（スコープ pos.stores:read を確認。詳細は storage/logs/laravel.log の [Smaregi]）';
                } else {
                    $list = $storesResult['stores'] ?? $storesResult;
                    $list = is_array($list) ? $list : [];
                    if (count($list) === 0) {
                        $posStatusMessage = '接続済み・POSなし（テスト環境のため店舗が存在しません）';
                    } else {
                        $posStatusMessage = '接続済み・店舗数: ' . count($list);
                    }
                }
            }
        }

        return new SmaregiSettingsView(
            $idpUrl,
            $webhookUrl,
            $appUrl,
            $linkedContractId,
            $posStatusMessage,
            $posStatusEnvCheck,
        );
    }
}
