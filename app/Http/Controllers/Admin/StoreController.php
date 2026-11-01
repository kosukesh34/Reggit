<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreInfoUpdateRequest;
use App\UseCases\Admin\Store\AdminActiveStoresUpdateUseCase;
use App\UseCases\Admin\Store\AdminStoreIndexUseCase;
use App\UseCases\Admin\Store\AdminStoreInfoUpdateUseCase;
use App\Util\StoreContext;
use Illuminate\Http\Request;

class StoreController extends AdminBaseController
{
    public function __construct(
        private AdminStoreIndexUseCase $indexUseCase,
        private AdminActiveStoresUpdateUseCase $activeStoresUseCase,
        private AdminStoreInfoUpdateUseCase $infoUseCase,
    ) {}

    public function index(Request $request)
    {
        return view('admin.stores.index', $this->indexUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->user('admin'),
            $this->isHeadquarters($request),
        ));
    }

    public function updateActiveStores(Request $request)
    {
        abort_unless($this->isHeadquarters($request), 403);
        $projectId = $this->resolveAdminProjectId();
        $limit = $this->activeStoresUseCase->limit($projectId);
        $request->merge(['store_ids' => $request->input('store_ids', [])]);
        $data = $request->validate([
            'store_ids' => ['required', 'array', 'min:1', 'max:'.$limit],
            'store_ids.*' => ['integer', 'distinct'],
        ], [
            'store_ids.required' => '利用する店舗を1件以上選択してください。',
            'store_ids.min' => '利用する店舗を1件以上選択してください。',
            'store_ids.max' => "利用できる店舗は最大{$limit}件です。選択する店舗数を減らしてください。",
        ]);
        $this->activeStoresUseCase->handle($projectId, array_map('intval', $data['store_ids']));

        return back()->with('success', '利用する店舗を更新しました。');
    }

    public function updateInfo(StoreInfoUpdateRequest $request, string $projectKey, int $id)
    {
        $this->infoUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->user('admin'),
            $this->isHeadquarters($request),
            $id,
            $request->info(),
        );

        return back()->with('success', '店舗情報を更新しました。');
    }

    public function select(Request $request, StoreContext $context)
    {
        $request->validate(['store_id' => ['required']]);
        $context->bind($request, $this->resolveAdminProjectId(), $request->user('admin'));
        session(["admin_store.{$context->projectId}" => $request->input('store_id')]);

        return redirect()->back(fallback: route('admin.dashboard', $this->buildAdminRouteParams()));
    }

    private function isHeadquarters(Request $request): bool
    {
        $admin = $request->user('admin');

        return $admin->isSystemAdmin() || $admin->isProjectAdmin();
    }
}
