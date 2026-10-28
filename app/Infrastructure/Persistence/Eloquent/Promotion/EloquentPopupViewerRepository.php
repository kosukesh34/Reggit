<?php

namespace App\Infrastructure\Persistence\Eloquent\Promotion;

use App\Domain\Promotion\Repositories\PopupViewerRepository;
use App\Domain\Promotion\ValueObjects\PopupViewer;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

final class EloquentPopupViewerRepository implements PopupViewerRepository
{
    public function findMember(int $projectId, int $memberId, ?int $selectedStoreId): ?PopupViewer
    {
        $member = Member::withoutGlobalScopes()->where('project_id', $projectId)->whereKey($memberId)->whereNull('deleted_at')
            ->first(['id', 'rank', 'customer_store_id']);
        if ($member === null) {
            return null;
        }
        $tagIds = DB::table('member_tag_members')->where('member_id', $memberId)->pluck('member_tag_id')->map(fn ($id): int => (int) $id)->all();
        $customerStoreId = $member->customer_store_id !== null ? (int) $member->customer_store_id : null;

        return new PopupViewer(true, $member->rank !== null ? (string) $member->rank : null, $tagIds, $selectedStoreId ?? $customerStoreId);
    }
}
