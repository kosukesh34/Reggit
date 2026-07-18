<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Member;
use App\Models\MemberVisit;

class AdminMemberVisitDetailOutput
{
    public function __construct(
        public readonly Member $member,
        public readonly MemberVisit $visit,
    ) {}
}
