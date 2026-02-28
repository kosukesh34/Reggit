<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 お知らせ設定 表示用。不変。 */
class AnnouncementsSettingsView
{
    public function __construct(
        public bool $featureEnabled,
        public bool $defaultPinned,
        public int $defaultExpiryDays
    ) {
    }
}
