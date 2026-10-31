<?php

namespace App\Services\Smaregi;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** スマレジの店舗一覧をプロジェクトの店舗に取り込む（新規は無効で作成、スマレジから消えた店舗は無効化）。 */
class SmaregiStoreListSyncService
{
    public function __construct(private SmaregiApiService $smaregiApi) {}

    public function refresh(int $projectId): void
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if ($contractId === null || $contractId === '') {
            return;
        }

        $result = $this->smaregiApi->getStores($contractId);
        if ($result === null) {
            return;
        }
        $remoteStores = isset($result['stores']) ? $result['stores'] : (isset($result['data']) && is_array($result['data']) ? $result['data'] : $result);
        if (! is_array($remoteStores)) {
            return;
        }

        $remoteById = [];
        foreach ($remoteStores as $remote) {
            if (! is_array($remote)) {
                continue;
            }
            $externalId = trim((string) ($remote['storeId'] ?? $remote['store_id'] ?? $remote['storeCode'] ?? $remote['id'] ?? ''));
            $name = trim((string) ($remote['storeName'] ?? $remote['store_name'] ?? $remote['store_name_1'] ?? $remote['name'] ?? ''));
            if ($externalId !== '' && $name !== '') {
                $remoteById[$externalId] = $name;
            }
        }
        if ($remoteById === []) {
            return;
        }

        DB::transaction(function () use ($projectId, $remoteById) {
            $legacy = Store::where('project_id', $projectId)->where('store_key', 'main')->whereNull('smaregi_store_id')->first();
            if ($legacy && count($remoteById) === 1 && Store::where('project_id', $projectId)->whereNotNull('smaregi_store_id')->doesntExist()) {
                $externalId = array_key_first($remoteById);
                $legacy->update(['smaregi_store_id' => $externalId, 'name' => $remoteById[$externalId], 'settings' => [...($legacy->settings ?? []), 'smaregi_available' => true]]);
            }
            foreach ($remoteById as $externalId => $name) {
                $store = Store::where('project_id', $projectId)->where('smaregi_store_id', $externalId)->first();
                if ($store) {
                    $store->update(['name' => $name, 'settings' => [...($store->settings ?? []), 'smaregi_available' => true]]);
                } else {
                    Store::create(['project_id' => $projectId, 'store_key' => 'smaregi-'.Str::lower(Str::random(12)), 'name' => $name, 'smaregi_store_id' => $externalId, 'is_active' => false, 'settings' => ['smaregi_available' => true]]);
                }
            }
            Store::where('project_id', $projectId)->whereNotNull('smaregi_store_id')->whereNotIn('smaregi_store_id', array_keys($remoteById))->get()
                ->each(fn (Store $store) => $store->update(['is_active' => false, 'settings' => [...($store->settings ?? []), 'smaregi_available' => false]]));
        });
    }
}
