<?php

namespace App\Services\Admin;

use App\Models\Member;
use App\Models\MemberTag;
use Illuminate\Support\Collection;

class MemberTagAdminService
{
    /**
     * @return Collection<int, MemberTag>
     */
    public function getListByProject(int $projectId): Collection
    {
        return MemberTag::where('project_id', $projectId)
            ->withCount('members')
            ->orderBy('name')
            ->get();
    }

    public function createTag(int $projectId, string $name, string $color): MemberTag
    {
        return MemberTag::create([
            'project_id' => $projectId,
            'name' => $name,
            'color' => $color,
        ]);
    }

    public function attachTagToMember(Member $member, int $tagId): void
    {
        MemberTag::where('project_id', $member->project_id)->findOrFail($tagId);
        $member->tags()->syncWithoutDetaching([$tagId]);
    }

    public function detachTagFromMember(Member $member, int $tagId): void
    {
        $member->tags()->detach($tagId);
    }

    public function destroyTag(int $projectId, int $tagId): void
    {
        MemberTag::where('project_id', $projectId)->findOrFail($tagId)->delete();
    }

    public function updateMemo(Member $member, ?string $memo): void
    {
        $member->update(['memo' => $memo]);
    }
}
