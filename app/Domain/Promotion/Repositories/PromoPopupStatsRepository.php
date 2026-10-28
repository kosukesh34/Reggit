<?php

namespace App\Domain\Promotion\Repositories;

interface PromoPopupStatsRepository
{
    /** その日の件数を1つ増やす。プロジェクトの、今表示しているポップアップでなければ false */
    public function record(int $projectId, int $popupId, string $event, \DateTimeInterface $date): bool;
}
