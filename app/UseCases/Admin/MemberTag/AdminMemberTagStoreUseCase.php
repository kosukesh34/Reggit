<?php

namespace App\UseCases\Admin\MemberTag;

use App\Models\MemberTag;
use App\Services\Admin\MemberTagAdminService;

class AdminMemberTagStoreUseCase
{
    private MemberTagAdminService $service;

    public function __construct(MemberTagAdminService $service)
    {
        $this->service = $service;
    }

    public function handle(int $projectId, string $name, string $color): MemberTag
    {
        return $this->service->createTag($projectId, $name, $color);
    }
}
