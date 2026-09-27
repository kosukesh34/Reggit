<?php

namespace App\Domain\Settings\Repositories;

interface MemberCardPreviewSettings
{
    public function getMemberRankSettings(int $projectId): array;

    public function getRankBasis(int $projectId): string;

    public function getRankAggregationType(int $projectId): string;

    public function getRankPeriodDays(int $projectId): int;

    public function getShowPointsDisplay(int $projectId): bool;

    public function getUsePoints(int $projectId): bool;

    public function getShowRankProgress(int $projectId): bool;

    public function getMemberCardDesign(int $projectId): string;

    public function getFrontendLogoUrl(int $projectId): ?string;

    public function getMemberCardDisplayType(int $projectId): string;

    public function getMemberCardCodeType(int $projectId): string;
}
