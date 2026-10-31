<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\Store;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\StoreContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreController extends AdminBaseController
{
    public function __construct(private SmaregiApiService $smaregiApi) {}

    public function index(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        if ($this->isHeadquarters($request)) {
            $this->refreshSmaregiStores($projectId);
        }
        $stores = Store::where('project_id', $projectId)->orderBy('id');
        if (! $this->isHeadquarters($request)) {
            $stores->whereHas('admins', fn ($q) => $q->whereKey($request->user('admin')->id));
        }
        $storeList = $stores->get();
        $availableSmaregiStores = $storeList->filter(fn (Store $store) => $store->smaregi_store_id !== null && ($store->settings['smaregi_available'] ?? false) === true);

        return view('admin.stores.index', [
            'stores' => $storeList,
            'availableSmaregiStores' => $availableSmaregiStores,
        ]);
    }

    public function updateActiveStores(Request $request)
    {
        abort_unless($this->isHeadquarters($request), 403);
        $projectId = $this->resolveAdminProjectId();
        $limit = (int) (Project::whereKey($projectId)->value('max_active_stores') ?? 1);
        $request->merge(['store_ids' => $request->input('store_ids', [])]);
        $data = $request->validate([
            'store_ids' => ['required', 'array', 'min:1', 'max:'.$limit],
            'store_ids.*' => ['integer', 'distinct'],
        ], [
            'store_ids.required' => '利用する店舗を1件以上選択してください。',
            'store_ids.min' => '利用する店舗を1件以上選択してください。',
            'store_ids.max' => "利用できる店舗は最大{$limit}件です。選択する店舗数を減らしてください。",
        ]);
        $stores = Store::where('project_id', $projectId)->whereNotNull('smaregi_store_id')->get()
            ->filter(fn (Store $store) => ($store->settings['smaregi_available'] ?? false) === true);
        $ids = array_map('intval', $data['store_ids']);
        abort_unless(count(array_diff($ids, $stores->pluck('id')->all())) === 0, 422, 'スマレジと同期した店舗を選択してください。');
        DB::transaction(function () use ($projectId, $ids) {
            Store::where('project_id', $projectId)->whereNotIn('id', $ids)->update(['is_active' => false]);
            Store::where('project_id', $projectId)->whereIn('id', $ids)->update(['is_active' => true]);
        });

        return back()->with('success', '利用する店舗を更新しました。');
    }

    public function select(Request $request, StoreContext $context)
    {
        $request->validate(['store_id' => ['required']]);
        $context->bind($request, $this->resolveAdminProjectId(), $request->user('admin'));
        session(["admin_store.{$context->projectId}" => $request->input('store_id')]);

        return redirect()->back(fallback: route('admin.dashboard', $this->buildAdminRouteParams()));
    }

    private function refreshSmaregiStores(int $projectId): void
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

    private function isHeadquarters(Request $request): bool
    {
        $admin = $request->user('admin');

        return $admin->isSystemAdmin() || $admin->isProjectAdmin();
    }
}
