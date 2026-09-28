<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Util\StoreContext;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementVisibility
{
    public function __construct(private readonly StoreContext $storeContext) {}

    public function apply(Builder $query, ?User $user): Builder
    {
        $memberId = $user === null ? null : Member::where('project_id', $user->project_id)->where('user_id', $user->id)->value('id');
        $storeId = $this->storeContext->store?->id;

        return $query->where(function ($outer) use ($memberId, $storeId) {
            $outer->where(function ($public) use ($storeId) {
                $public->where('visibility', 'public');
                $public->where(function ($stores) use ($storeId) {
                    $stores->whereNull('store_ids');
                    if ($storeId !== null) {
                        $stores->orWhereJsonContains('store_ids', (int) $storeId);
                    }
                });
            });
            if ($memberId !== null) {
                $outer->orWhere(function ($private) use ($memberId) {
                    $private->where('visibility', 'recipients')->whereJsonContains('target_member_ids', (int) $memberId);
                });
            }
        });
    }
}
