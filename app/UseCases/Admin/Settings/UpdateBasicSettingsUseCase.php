<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\OperationalSettings;
use App\UseCases\Admin\Settings\Data\UpdateBasicSettingsInput;

class UpdateBasicSettingsUseCase
{
    public function __construct(private OperationalSettings $settings) {}

    public function handle(UpdateBasicSettingsInput $input): void
    {
        $this->settings->setValue('business_hours_start', $input->hoursStart, 'time', $input->projectId);
        $this->settings->setValue('business_hours_end', $input->hoursEnd, 'time', $input->projectId);
        $this->settings->setOrderMode($input->orderMode, $input->projectId);
        $this->settings->setOrderLineNotifyEnabled($input->notifyOrder, $input->projectId);
        $this->settings->setOrderReadyLineNotifyEnabled($input->notifyReady, $input->projectId);
    }
}
