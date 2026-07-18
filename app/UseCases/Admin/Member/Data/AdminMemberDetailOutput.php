<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Member;

/**
 * 管理画面会員詳細 UseCase の出力。不変。
 *
 * @param  array<string, mixed>  $purchaseSummary
 */
class AdminMemberDetailOutput
{
    public function __construct(
        public Member $member,
        public array $purchaseSummary = []
    ) {}
}
