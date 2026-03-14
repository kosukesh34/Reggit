<?php

namespace App\UseCases\Admin\MemberTag;

use App\Services\Admin\MemberTagAdminService;

class AdminMemberTagDestroyUseCase
{
    private MemberTagAdminService $service;

    public function __construct(MemberTagAdminService $service)
    {
        $this->service = $service;
    }

    public function handle(int $projectId, int $tagId): void
    {
        $this->service->destroyTag($projectId, $tagId);
    }
}
