<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\MemberAudienceGroup;
use App\Services\Admin\MemberSegmentService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminMemberAudienceGroupUseCase
{
    public function __construct(
        private MemberSegmentService $memberSegmentService,
    ) {}

    /** @return Collection<int, MemberAudienceGroup> */
    public function list(int $projectId): Collection
    {
        return MemberAudienceGroup::query()
            ->where('project_id', $projectId)
            ->orderBy('name')
            ->get();
    }

    /** @param  array<string, mixed>  $input */
    public function store(int $projectId, string $name, array $input): MemberAudienceGroup
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'audience_group_name' => 'グループ名を入力してください',
            ]);
        }
        if (mb_strlen($name) > 40) {
            throw ValidationException::withMessages([
                'audience_group_name' => 'グループ名は40文字以内で入力してください',
            ]);
        }

        $criteria = $this->memberSegmentService->buildCriteriaFromInput($input);
        if (is_array($criteria)) {
            unset($criteria['audience_group_ids']);
        }
        if ($criteria === null || array_diff_key($criteria, ['operators' => true, 'match_mode' => true]) === []) {
            throw ValidationException::withMessages([
                'audience_group_name' => 'グループにする条件を1つ以上選んでください',
            ]);
        }

        return MemberAudienceGroup::query()->updateOrCreate(
            ['project_id' => $projectId, 'name' => $name],
            ['criteria' => $criteria],
        );
    }

    public function delete(int $projectId, int $id): void
    {
        MemberAudienceGroup::query()
            ->where('project_id', $projectId)
            ->whereKey($id)
            ->delete();
    }
}
