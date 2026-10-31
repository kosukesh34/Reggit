<?php

namespace App\Domain\Settings\Repositories;

interface HomeSettingsRepository extends SettingValueReader, SettingValueWriter
{
    /** @return list<array<string, mixed>> */
    public function defaultFrontendMenuItems(): array;

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string;

    /** @param list<array<string, mixed>> $items */
    public function setFrontendMenuItems(array $items, int $projectId): void;
}
