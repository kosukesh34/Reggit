<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\Services\Admin\StampAdminService;

class StampController extends AdminBaseController
{
    public function __construct(
        private StampAdminService $stampAdminService
    ) {
    }

    public function stamps()
    {
        $stampCards = $this->stampAdminService->getPaginatedList(20);
        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        return view('admin.stamps.create');
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->stampAdminService->create($this->resolveAdminProjectId(), $request->validated());
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(int $id)
    {
        $stampCard = $this->stampAdminService->findOrFail($id);
        return view('admin.stamps.edit', compact('stampCard'));
    }

    public function updateStamp(StampUpdateRequest $request, int $id)
    {
        $this->stampAdminService->update($id, $request->validated());
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを更新しました');
    }

    public function deleteStamp(int $id)
    {
        $this->stampAdminService->delete($id);
        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを削除しました');
    }
}
