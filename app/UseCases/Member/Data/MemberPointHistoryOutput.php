<?php

namespace App\UseCases\Member\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** ポイント履歴 UseCase の出力。不変。 */
class MemberPointHistoryOutput
{
    public function __construct(
        public bool $requiresMembership,
        public ?LengthAwarePaginator $transactions
    ) {}
}
