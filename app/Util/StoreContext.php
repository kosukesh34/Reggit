<?php

namespace App\Util;

use App\Models\AdminUser;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Request scoped store selection. null IDs means an explicitly authorized headquarters view. */
class StoreContext
{
    public ?int $projectId = null;

    public ?Store $store = null;

    public ?array $visibleStoreIds = null;

    public ?AdminUser $admin = null;

    public bool $bound = false;

    public int $projectStoreCount = 0;

    public function bind(Request $request, int $projectId, ?AdminUser $admin = null): void
    {
        $this->projectId = $projectId;
        $this->admin = $admin;
        $this->bound = true;
        $this->projectStoreCount = Store::where('project_id', $projectId)->count();
        $stores = Store::where('project_id', $projectId)->where('is_active', true)->orderBy('id');
        $headquarters = $admin === null || $admin->isSystemAdmin() || $admin->isProjectAdmin();
        if (! $headquarters) {
            $stores->whereHas('admins', fn ($q) => $q->where('admin_users.id', $admin->id));
        }
        $allowed = $stores->get();
        $explicitSelection = $request->has('store_id') || $request->header('X-Store-Id') !== null;
        $selection = $request->input('store_id') ?? $request->header('X-Store-Id');
        if ($selection === null && $admin !== null) {
            $selection = session("admin_store.{$projectId}");
        }
        if (! $explicitSelection && $selection !== null && (($selection === 'all' && ! $headquarters) || ($selection !== 'all' && ! $allowed->contains('id', (int) $selection)))) {
            session()->forget("admin_store.{$projectId}");
            $selection = null;
        }
        if ($selection === 'all') {
            abort_unless($admin !== null && $headquarters, 403, '全店舗を選択する権限がありません。');
            $this->store = null;
            $this->visibleStoreIds = null;
        } elseif ($selection !== null && $selection !== '') {
            $this->store = $allowed->firstWhere('id', (int) $selection);
            abort_unless($this->store !== null, 403, 'この店舗へのアクセス権がありません。');
            $this->visibleStoreIds = [(int) $this->store->id];
        } elseif ($allowed->count() === 1) {
            $this->store = $allowed->first();
            $this->visibleStoreIds = [(int) $this->store->id];
        } else {
            $this->store = null;
            $this->visibleStoreIds = $admin !== null && ! $headquarters ? $allowed->pluck('id')->all() : null;
        }
        if ($admin === null && $this->projectStoreCount > 1 && $this->store === null) {
            $path = $request->path();
            if (preg_match('~(^|/)queue(/|$)~', $path) || (! $request->isMethod('GET') && preg_match('~(^|/)(orders|reservations)(/|$)~', $path))) {
                throw ValidationException::withMessages(['store_id' => 'ご利用店舗を選択してください。']);
            }
        }
        if ($admin !== null) {
            if ($explicitSelection && $request->hasSession()) {
                $request->session()->put("admin_store.{$projectId}", $this->store?->id ?? 'all');
            }
            view()->share('availableStores', $allowed);
            view()->share('selectedStore', $this->store);
            view()->share('canManageStores', $headquarters);
        }
    }

    /** 全店舗表示から店舗別の業務画面に入る際の既定店舗。選択中の全店舗セッションは維持する。 */
    public function useDefaultForOperation(): void
    {
        if ($this->store !== null || $this->projectId === null) {
            return;
        }

        $stores = Store::where('project_id', $this->projectId)->where('is_active', true)->orderBy('id');
        if ($this->admin !== null && ! $this->admin->isSystemAdmin() && ! $this->admin->isProjectAdmin()) {
            $stores->whereHas('admins', fn ($query) => $query->where('admin_users.id', $this->admin->id));
        }
        $store = $stores->first();
        abort_unless($store !== null, 422, '利用できる店舗がありません。');

        $this->store = $store;
        $this->visibleStoreIds = [(int) $store->id];
        view()->share('selectedStore', $store);
    }

    public function runForStore(Store $store, \Closure $operation): mixed
    {
        $previous = [$this->projectId, $this->store, $this->visibleStoreIds, $this->admin, $this->bound, $this->projectStoreCount];
        $this->projectId = (int) $store->project_id;
        $this->projectStoreCount = Store::where('project_id', $store->project_id)->count();
        $this->store = $store;
        $this->visibleStoreIds = [(int) $store->id];
        $this->admin = null;
        $this->bound = true;
        try {
            return $operation();
        } finally {
            [$this->projectId, $this->store, $this->visibleStoreIds, $this->admin, $this->bound, $this->projectStoreCount] = $previous;
        }
    }

    /** Run deferred work with its captured selection, including headquarters or unbound views. */
    public function runWithContext(self $context, \Closure $operation): mixed
    {
        $previous = [$this->projectId, $this->store, $this->visibleStoreIds, $this->admin, $this->bound, $this->projectStoreCount];
        [$this->projectId, $this->store, $this->visibleStoreIds, $this->admin, $this->bound, $this->projectStoreCount] =
            [$context->projectId, $context->store, $context->visibleStoreIds, $context->admin, $context->bound, $context->projectStoreCount];
        try {
            return $operation();
        } finally {
            [$this->projectId, $this->store, $this->visibleStoreIds, $this->admin, $this->bound, $this->projectStoreCount] = $previous;
        }
    }

    public function resolveForWrite(int $projectId): int
    {
        if ($this->bound) {
            if ($this->projectId !== $projectId || $this->store === null) {
                throw ValidationException::withMessages(['store_id' => '操作する店舗を選択してください。']);
            }

            return (int) $this->store->id;
        }
        $stores = Store::where('project_id', $projectId)->where('is_active', true)->limit(2)->get();
        if ($stores->count() !== 1) {
            throw ValidationException::withMessages(['store_id' => '店舗IDの指定が必要です。']);
        }

        return (int) $stores->first()->id;
    }

    public function assertStore(int $projectId, int $storeId): void
    {
        abort_unless(Store::where('project_id', $projectId)->whereKey($storeId)->where('is_active', true)->exists(), 422, '店舗が無効です。');
        if ($this->bound && $this->admin !== null) {
            abort_unless($this->projectId === $projectId && ($this->visibleStoreIds === null || in_array($storeId, $this->visibleStoreIds, true)), 403);
        }
    }
}
