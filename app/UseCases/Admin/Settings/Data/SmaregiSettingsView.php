<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * スマレジ連携設定画面用の表示データ。
 */
class SmaregiSettingsView
{
    public function __construct(
        public readonly string $idpUrl,
        public readonly string $webhookUrl,
        public readonly string $appUrl,
        public readonly string $linkedContractId,
    ) {}
}
