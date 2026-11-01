<?php

namespace App\UseCases\Store;

use App\Models\Store;
use Illuminate\Support\Collection;

class ActiveStoreListUseCase
{
    /** @return Collection<int, Store> 会員アプリで選べる店舗（利用中のみ） */
    public function handle(int $projectId): Collection
    {
        return Store::where('project_id', $projectId)->where('is_active', true)->orderBy('id')->get(['id', 'store_key', 'name', 'address', 'phone', 'latitude', 'longitude']);
    }
}
