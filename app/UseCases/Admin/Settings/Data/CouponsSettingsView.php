<?php

namespace App\UseCases\Admin\Settings\Data;

/** 管理画面 クーポン設定 表示用。不変。 */
class CouponsSettingsView
{
    public function __construct(
        public string $couponDefaultImageUrl,
        public bool $featureCouponsEnabled
    ) {
    }
}
