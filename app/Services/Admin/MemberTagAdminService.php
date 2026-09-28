<?php

namespace App\Services\Admin;

use App\Models\Member;
use App\Models\MemberTag;
use App\Util\StoreContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemberTagAdminService
{
    public function __construct(private readonly StoreContext $storeContext) {}

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
        $context = $this->storeContext;
        if ($context->projectStoreCount > 1 && $context->store !== null && $context->admin !== null) {
            DB::table('member_stores')->where('project_id', $member->project_id)->where('member_id', $member->id)->where('store_id', $context->store->id)->update(['memo' => $memo, 'updated_at' => now()]);

            return;
        }
        abort_unless($context->projectStoreCount <= 1 || $context->admin === null || $context->admin->isSystemAdmin() || $context->admin->isProjectAdmin(), 403);
        $member->update(['memo' => $memo]);
    }
}
