<?php

namespace App\Domain\Settings\Repositories;

interface OrderSettings
{
    public function getOrderMode(int $projectId): string;

    public function getPointEarnRateForRank(int $projectId, ?string $rankKey): int;

    public function getPointsPerOrder(int $projectId): int;

    public function getPointsValidDays(int $projectId): int;

    public function getStampEarnPerAmountForRank(int $projectId, ?string $rankKey): int;

    public function getUsePoints(int $projectId): bool;

    public function getUseStamps(int $projectId): bool;

    public function getUseTransactionStamps(int $projectId): bool;
}
