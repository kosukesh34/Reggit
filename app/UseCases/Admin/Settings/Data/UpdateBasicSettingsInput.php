<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateBasicSettingsInput
{
    public function __construct(public int $projectId, public string $hoursStart, public string $hoursEnd, public string $orderMode, public bool $notifyOrder, public bool $notifyReady) {}
}
