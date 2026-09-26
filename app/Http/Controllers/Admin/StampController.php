<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\UseCases\Admin\Stamp\AdminStampCreateFormUseCase;
use App\UseCases\Admin\Stamp\AdminStampDeleteUseCase;
use App\UseCases\Admin\Stamp\AdminStampEditUseCase;
use App\UseCases\Admin\Stamp\AdminStampIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampUpdateUseCase;

class StampController extends AdminBaseController
{
    public function __construct(
        private AdminStampIndexUseCase $indexUseCase,
        private AdminStampCreateFormUseCase $createFormUseCase,
        private AdminStampStoreUseCase $storeUseCase,
        private AdminStampEditUseCase $editUseCase,
        private AdminStampUpdateUseCase $updateUseCase,
        private AdminStampDeleteUseCase $deleteUseCase,
    ) {}

    public function stamps()
    {
        $stampCards = $this->indexUseCase->handle();

        return view('admin.stamps.index', compact('stampCards'));
    }

    public function createStamp()
    {
        $output = $this->createFormUseCase->handle($this->resolveAdminProjectId());

        return view('admin.stamps.create', ['view' => $output]);
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(string $projectKey, $id)
    {
        $output = $this->editUseCase->handle($this->resolveAdminProjectId(), (int) $id);

        return view('admin.stamps.edit', ['view' => $output]);
    }

    public function updateStamp(StampUpdateRequest $request, string $projectKey, $id)
    {
        $this->updateUseCase->handle((int) $id, $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを更新しました');
    }

    public function deleteStamp(string $projectKey, $id)
    {
        $this->deleteUseCase->handle((int) $id);

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを削除しました');
    }
}
