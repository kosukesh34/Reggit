<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\MemberAdminService;

class MemberController extends AdminBaseController
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {
    }

    public function members()
    {
        $members = $this->memberAdminService->getPaginatedList(20);
        return view('admin.members', compact('members'));
    }

    public function memberDetail($id)
    {
        $member = $this->memberAdminService->findOrFail((int) $id);
        return view('admin.member-detail', compact('member'));
    }
}
