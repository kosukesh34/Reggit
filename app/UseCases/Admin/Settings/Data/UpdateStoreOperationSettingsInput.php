<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateStoreOperationSettingsInput
{
    public function __construct(
        public int $projectId,
        public string $businessHoursStart,
        public string $businessHoursEnd,
        public string $orderMode,
        public bool $orderLineNotifyEnabled,
        public bool $orderReadyLineNotifyEnabled,
    ) {}
}
