<?php

namespace App\UseCases\Member\Dto;

use Illuminate\Support\Collection;

class MemberPointsResultDto
{
    /** @param Collection<int, \App\Models\PointTransaction> $transactions */
    public function __construct(
        public bool $hasMember,
        public int $points,
        public Collection $transactions
    ) {
    }
}
