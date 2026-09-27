<?php

namespace App\Domain\Settings\Repositories;

interface OrderNotificationSettings extends LineConnectionSettings
{
    public function isOrderLineNotifyEnabled(int $projectId): bool;

    public function isOrderReadyLineNotifyEnabled(int $projectId): bool;
}
