<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Member;

/**
 * 管理画面会員編集フォーム UseCase の出力。不変。
 *
 * @param array<string, string> $rankOptions key => label
 */
class AdminMemberEditOutput
{
    public function __construct(
        public Member $member,
        public array $rankOptions
    ) {
    }
}
