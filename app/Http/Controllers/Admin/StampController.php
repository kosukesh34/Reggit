<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\UseCases\Admin\Stamp\AdminStampDeleteUseCase;
use App\UseCases\Admin\Stamp\AdminStampEditUseCase;
use App\UseCases\Admin\Stamp\AdminStampIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampUpdateUseCase;

class StampController extends AdminBaseController
{
    private AdminStampIndexUseCase $indexUseCase;
    private AdminStampStoreUseCase $storeUseCase;
    private AdminStampEditUseCase $editUseCase;
    private AdminStampUpdateUseCase $updateUseCase;
    private AdminStampDeleteUseCase $deleteUseCase;

    public function __construct(
        AdminStampIndexUseCase $indexUseCase,
        AdminStampStoreUseCase $storeUseCase,
        AdminStampEditUseCase $editUseCase,
        AdminStampUpdateUseCase $updateUseCase,
        AdminStampDeleteUseCase $deleteUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
    }

    public function stamps()
    {
        $stampCards = $this->indexUseCase->handle();

        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        return view('admin.stamps.create');
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    /** @param string|int $id */
    public function editStamp($id)
    {
        $stampCard = $this->editUseCase->handle((int) $id);

        return view('admin.stamps.edit', compact('stampCard'));
    }

    /** @param string|int $id */
    public function updateStamp(StampUpdateRequest $request, $id)
    {
        $this->updateUseCase->handle((int) $id, $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを更新しました');
    }

    /** @param string|int $id */
    public function deleteStamp($id)
    {
        $this->deleteUseCase->handle((int) $id);

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを削除しました');
    }
}
