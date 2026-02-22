<?php

namespace App\UseCases\Member\Data;

use Illuminate\Support\Collection;

/** ポイント照会 UseCase の実行結果。不変。 @param Collection<int, \App\Models\PointTransaction> $transactions */
class MemberPointsResult
{
    public function __construct(
        public bool $hasMember,
        public int $points,
        public Collection $transactions
    ) {
    }
}
