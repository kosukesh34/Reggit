<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 順番待ち設定 表示用。 */
class QueueSettingsView
{
    public function __construct(
        public int $avgServeMinutes,
        public bool $requireMembership,
        public bool $channelLine,
        public bool $channelNative,
    ) {}
}
