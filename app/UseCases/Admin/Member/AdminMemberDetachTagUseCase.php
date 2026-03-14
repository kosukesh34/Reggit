<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use App\Services\Admin\MemberTagAdminService;

class AdminMemberDetachTagUseCase
{
    private MemberAdminService $memberService;

    private MemberTagAdminService $tagService;

    public function __construct(MemberAdminService $memberService, MemberTagAdminService $tagService)
    {
        $this->memberService = $memberService;
        $this->tagService = $tagService;
    }

    public function handle(int $projectId, int $memberId, int $tagId): void
    {
        $member = $this->memberService->findOrFail($memberId, $projectId);
        $this->tagService->detachTagFromMember($member, $tagId);
    }
}
