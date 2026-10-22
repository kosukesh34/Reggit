<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\OperationalSettings;
use App\UseCases\Admin\Settings\Data\UpdateStoreOperationSettingsInput;

class UpdateStoreOperationSettingsUseCase
{
    public function __construct(private OperationalSettings $settings) {}

    public function handle(UpdateStoreOperationSettingsInput $input): void
    {
        $this->settings->setValue('business_hours_start', $input->businessHoursStart, 'time', $input->projectId);
        $this->settings->setValue('business_hours_end', $input->businessHoursEnd, 'time', $input->projectId);
        $this->settings->setOrderMode($input->orderMode, $input->projectId);
        $this->settings->setOrderLineNotifyEnabled($input->orderLineNotifyEnabled, $input->projectId);
        $this->settings->setOrderReadyLineNotifyEnabled($input->orderReadyLineNotifyEnabled, $input->projectId);
    }
}
