<?php

namespace App\UseCases\Admin\Settings\Data;

/** 機能設定画面用の表示データ。 */
final readonly class FeatureSettingsView
{
    public function __construct(
        public array $featureFlags,
        public array $featureFlagLabels,
        public bool $usePoints,
        public bool $useTransactionStamps
    ) {}
}
