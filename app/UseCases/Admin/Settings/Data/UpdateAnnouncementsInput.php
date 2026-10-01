<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateAnnouncementsInput
{
    public function __construct(public int $projectId, public bool $defaultPinned, public int $defaultExpiryDays) {}
}
