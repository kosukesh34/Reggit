<?php

namespace App\Domain\Settings\Repositories;

interface StampSettings
{
    public function getUseStamps(int $projectId): bool;

    public function getStampDefaultImageUrl(int $projectId): ?string;
}
