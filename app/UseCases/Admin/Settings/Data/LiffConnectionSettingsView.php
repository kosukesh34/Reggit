<?php

namespace App\UseCases\Admin\Settings\Data;

/**
 * LINE / LIFF 接続設定画面用の表示データ。
 */
class LiffConnectionSettingsView
{
    public function __construct(
        public readonly string $lineLiffId,
        public readonly string $lineLiffUrl,
        public readonly string $liffEntryUrl,
        public readonly string $lineFriendAddUrl,
        public readonly bool $hasChannelAccessToken,
        public readonly bool $hasChannelSecret,
    ) {}
}
