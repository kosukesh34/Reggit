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
        /** 接続・POS状態（開発用・後で削除可）。null のときは非表示 */
        public readonly ?string $posStatusMessage = null,
        /** トークン失敗時のみ。.env の読み込み状況（値は出さず設定済み/未設定のみ）。null のときは非表示 */
        public readonly ?string $posStatusEnvCheck = null,
    ) {
    }
}
