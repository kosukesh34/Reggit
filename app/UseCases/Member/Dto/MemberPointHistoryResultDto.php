<?php

namespace App\UseCases\Member\Dto;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberPointHistoryResultDto
{
    public function __construct(
        public bool $requiresMembership,
        public ?LengthAwarePaginator $transactions
    ) {
    }
}
