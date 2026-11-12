<?php

namespace App\Domain\Settings\Repositories;

interface FrontendShellSettings
{
    public function getLineThemeColors(int $projectId): array;

    public function getFrontendMenuItems(int $projectId): array;

    public function defaultFrontendMenuItems(): array;

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string;

    public function getFeatureFlags(int $projectId): array;

    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getFrontendHeaderText(int $projectId): ?string;

    public function getOrderMode(int $projectId): string;

    public function getLiffTabSettings(int $projectId): array;

    public function getLineLiffId(int $projectId): string;

    public function getLineLiffUrl(int $projectId): string;

    public function getLineFriendAddUrl(int $projectId): string;

    public function getQueueRequireMembership(int $projectId): bool;

    public function getMemberRegistrationMode(int $projectId): string;
}
