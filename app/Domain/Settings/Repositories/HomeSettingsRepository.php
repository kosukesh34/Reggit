<?php

namespace App\Domain\Settings\Repositories;

interface HomeSettingsRepository extends SettingValueReader, SettingValueWriter
{
    /** @return list<array<string, mixed>> */
    public function defaultFrontendMenuItems(): array;

    /** @param list<array<string, mixed>> $items */
    public function setFrontendMenuItems(array $items, int $projectId): void;
}
