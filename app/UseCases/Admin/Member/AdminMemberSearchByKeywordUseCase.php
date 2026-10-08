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

    /**
     * CSV などで受け取った会員番号の並びから会員を探し、見つからない番号も返す。
     *
     * @return array{data: Collection<int, array{id: int, member_number: string, name: string}>, missing: list<string>}
     */
    public function findByNumberList(int $projectId, string $numbers): array
    {
        $requested = array_values(array_unique(array_filter(preg_split('/[\s,]+/', trim($numbers)) ?: [])));
        $members = $this->memberAdminService->findByMemberNumbers($projectId, $requested);

        return [
            'data' => $members,
            'missing' => array_values(array_diff($requested, $members->pluck('display_identifier')->all())),
        ];
    }
}
