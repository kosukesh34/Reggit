<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Store;
use Illuminate\Support\Collection;

class AnnouncementStoreAudience
{
    public function filter(Announcement $announcement, Collection $ids): Collection
    {
        $query = Member::withoutGlobalScopes()->where('project_id', $announcement->project_id)->whereIn('id', $ids->all())->whereNull('deleted_at');
        if (is_array($announcement->store_ids)) {
            $query->whereHas('stores', fn ($q) => $q->where('stores.project_id', $announcement->project_id)->whereIn('stores.id', $announcement->store_ids)->where('stores.is_active', true));
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function authorizedForSend(Announcement $announcement): bool
    {
        if ($announcement->created_by_admin_id === null) {
            return true; // migrated deliveries
        }
        $admin = AdminUser::find($announcement->created_by_admin_id);
        if ($admin === null || ! $admin->canAccessProject((int) $announcement->project_id) || ! $admin->hasPermission('announcements.manage')) {
            return false;
        }
        if ($announcement->store_ids === null) {
            return $admin->isSystemAdmin() || $admin->isProjectAdmin();
        }
        $ids = $announcement->store_ids;
        $stores = Store::where('project_id', $announcement->project_id)->whereIn('id', $ids)->where('is_active', true)->get();
        if ($stores->count() !== count($ids)) {
            return false;
        }

        return $admin->isSystemAdmin() || $admin->isProjectAdmin()
            || $admin->stores()->where('stores.project_id', $announcement->project_id)->whereIn('stores.id', $ids)->count() === count($ids);
    }
}
