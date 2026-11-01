<?php

namespace App\UseCases\Admin\Store;

use App\Models\AdminUser;
use App\Models\Store;

class AdminStoreInfoUpdateUseCase
{
    /**
     * 本部は全店舗、それ以外は担当店舗のみ更新できる。
     *
     * @param  array{address: ?string, phone: ?string, latitude: ?float, longitude: ?float}  $info
     */
    public function handle(int $projectId, AdminUser $admin, bool $headquarters, int $storeId, array $info): void
    {
        Store::where('project_id', $projectId)
            ->when(! $headquarters, fn ($query) => $query->whereHas('admins', fn ($q) => $q->whereKey($admin->id)))
            ->findOrFail($storeId)
            ->update($info);
    }
}
