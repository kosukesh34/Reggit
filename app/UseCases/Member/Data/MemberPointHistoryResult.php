<?php

namespace App\UseCases\Member\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** ポイント履歴 UseCase の実行結果。不変。 */
class MemberPointHistoryResult
{
    public function __construct(
        public bool $requiresMembership,
        public ?LengthAwarePaginator $transactions
    ) {
    }
}
