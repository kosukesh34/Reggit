<?php

namespace App\Domain\Promotion\Repositories;

use App\Domain\Promotion\ValueObjects\PromoPopupContent;

interface PromoPopupRepository
{
    /** @return list<PromoPopupContent> 表示期間中で有効なもの。優先度の高い順 */
    public function findShowing(int $projectId, \DateTimeInterface $now, int $limit): array;
}
