<?php

namespace App\UseCases\Admin\Store;

use App\Models\AdminUser;
use App\Models\Store;
use App\Services\Smaregi\SmaregiStoreListSyncService;
use Illuminate\Support\Collection;

class AdminStoreIndexUseCase
{
    public function __construct(private SmaregiStoreListSyncService $smaregiStores) {}

    /**
     * 本部はスマレジの店舗一覧を取り込んでから全店舗、それ以外は担当店舗のみ。
     *
     * @return array{stores: Collection<int, Store>, availableSmaregiStores: Collection<int, Store>}
     */
    public function handle(int $projectId, AdminUser $admin, bool $headquarters): array
    {
        if ($headquarters) {
            $this->smaregiStores->refresh($projectId);
        }
        $stores = Store::where('project_id', $projectId)->orderBy('id');
        if (! $headquarters) {
            $stores->whereHas('admins', fn ($q) => $q->whereKey($admin->id));
        }
        $storeList = $stores->get();

        return [
            'stores' => $storeList,
            'availableSmaregiStores' => $storeList->filter(fn (Store $store) => $store->smaregi_store_id !== null && ($store->settings['smaregi_available'] ?? false) === true),
        ];
    }
}
