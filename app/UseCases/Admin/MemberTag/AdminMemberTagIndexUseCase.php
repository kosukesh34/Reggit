<?php

namespace App\UseCases\Admin\MemberTag;

use App\Services\Admin\MemberTagAdminService;
use Illuminate\Support\Collection;

class AdminMemberTagIndexUseCase
{
    private MemberTagAdminService $service;

    public function __construct(MemberTagAdminService $service)
    {
        $this->service = $service;
    }

    /** @return Collection<int, \App\Models\MemberTag> */
    public function handle(int $projectId): Collection
    {
        return $this->service->getListByProject($projectId);
    }
}
