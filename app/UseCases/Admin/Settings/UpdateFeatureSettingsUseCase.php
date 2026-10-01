<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\UseCases\Admin\Settings\Data\UpdateFeatureSettingsInput;

class UpdateFeatureSettingsUseCase
{
    public function __construct(private FeatureSettings $settings) {}

    public function handle(UpdateFeatureSettingsInput $input): void
    {
        $flags = $this->settings->withPreservedLotteryManagedFlags($input->features, $input->projectId);
        $this->settings->setFeatureFlags($flags, $input->projectId);
        $useStamps = $this->settings->isFeatureEnabled('feature_stamps_enabled', $input->projectId);
        $this->settings->setValue('use_stamps', $useStamps, 'boolean', $input->projectId);
        if ($input->usePoints !== null) {
            $this->settings->setValue('use_points', $input->usePoints, 'boolean', $input->projectId);
        }
        if ($input->useTransactionStamps !== null) {
            $this->settings->setValue('use_transaction_stamps', $useStamps && $input->useTransactionStamps, 'boolean', $input->projectId);
        }
        if (! $useStamps) {
            $this->settings->setValue('use_transaction_stamps', false, 'boolean', $input->projectId);
        }
    }
}
