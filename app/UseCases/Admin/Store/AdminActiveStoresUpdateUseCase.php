<?php

namespace App\UseCases\Admin\Store;

use App\Models\Project;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class AdminActiveStoresUpdateUseCase
{
    /** 契約で同時に利用できる店舗数の上限 */
    public function limit(int $projectId): int
    {
        return (int) (Project::whereKey($projectId)->value('max_active_stores') ?? 1);
    }

    /** @param list<int> $storeIds スマレジと同期済みの店舗だけを利用中にする */
    public function handle(int $projectId, array $storeIds): void
    {
        $stores = Store::where('project_id', $projectId)->whereNotNull('smaregi_store_id')->get()
            ->filter(fn (Store $store) => ($store->settings['smaregi_available'] ?? false) === true);
        abort_unless(count(array_diff($storeIds, $stores->pluck('id')->all())) === 0, 422, 'スマレジと同期した店舗を選択してください。');
        DB::transaction(function () use ($projectId, $storeIds) {
            Store::where('project_id', $projectId)->whereNotIn('id', $storeIds)->update(['is_active' => false]);
            Store::where('project_id', $projectId)->whereIn('id', $storeIds)->update(['is_active' => true]);
        });
    }
}
