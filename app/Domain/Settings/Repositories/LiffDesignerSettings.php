<?php

namespace App\Domain\Settings\Repositories;

interface LiffDesignerSettings extends SettingValueReader, SettingValueWriter
{
    public function defaultFrontendMenuItems(): array;

    public function getFeatureFlags(int $projectId): array;

    public function getLiffCustomCss(int $projectId): string;

    public function getLiffDetailLayouts(int $projectId): array;

    public function getLiffSavedScreens(int $projectId): array;

    public function getLiffTabLayouts(int $projectId): array;

    public function getLiffTabSettings(int $projectId): array;

    public function getLineFriendAddUrl(int $projectId): string;

    public function liffSavedScreensMax(): int;

    public function normalizeFrontendMenuIcon(string $tab, string $icon): string;

    public function setFrontendMenuItems(array $items, int $projectId): void;

    public function setLiffCustomCss(string $css, int $projectId): void;

    public function setLiffDetailLayouts(array $layouts, int $projectId): void;

    public function setLiffSavedScreens(array $screens, int $projectId): void;

    public function setLiffTabLayouts(array $layouts, int $projectId): void;

    public function setLiffTabSettings(array $settings, int $projectId): void;

    public function defaultLiffDesignerTemplates(): array;

    public function setMemberCardDesign(string $value, int $projectId): void;

    public function setMemberCardDisplayType(string $value, int $projectId): void;

    public function setMemberCardCodeType(string $value, int $projectId): void;

    public function setMemberRegistrationMode(string $mode, int $projectId): void;
}
