<?php

namespace App\Domain\Settings\Repositories;

/** 営業時間と休業日。選択中の店舗で上書きした値を返す */
interface BusinessHoursSettings
{
    /** @return array{start: string, end: string} */
    public function getBusinessHours(int $projectId): array;

    public function isDateAvailable(string $date, int $projectId): bool;
}
