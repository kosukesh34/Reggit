<?php

namespace App\UseCases\Member\Data;

use Illuminate\Support\Collection;

/** ポイント照会 UseCase の出力。不変。 @param Collection<int, \App\Models\PointTransaction> $transactions */
class MemberPointsOutput
{
    public function __construct(
        public bool $hasMember,
        public int $points,
        public Collection $transactions
    ) {
    }
}
