<?php

namespace App\UseCases\Admin\Member\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** 管理画面会員一覧 UseCase の出力。不変。 */
class AdminMemberListOutput
{
    public function __construct(
        public LengthAwarePaginator $members
    ) {}
}
