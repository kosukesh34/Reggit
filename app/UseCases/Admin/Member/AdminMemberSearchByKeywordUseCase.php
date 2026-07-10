<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberAdminService;
use Illuminate\Support\Collection;

class AdminMemberSearchByKeywordUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    /**
     * @return Collection<int, array{id: int, member_number: string, name: string}>
     */
    public function handle(int $projectId, string $keyword, int $limit = 20): Collection
    {
        return $this->memberAdminService->searchByKeyword($projectId, trim($keyword), $limit);
    }
}
