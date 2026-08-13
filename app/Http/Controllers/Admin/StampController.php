<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StampStoreRequest;
use App\Http\Requests\Admin\StampUpdateRequest;
use App\UseCases\Admin\Stamp\AdminStampDeleteUseCase;
use App\UseCases\Admin\Stamp\AdminStampEditUseCase;
use App\UseCases\Admin\Stamp\AdminStampGrantQrUseCase;
use App\UseCases\Admin\Stamp\AdminStampIndexUseCase;
use App\UseCases\Admin\Stamp\AdminStampStoreUseCase;
use App\UseCases\Admin\Stamp\AdminStampUpdateUseCase;

class StampController extends AdminBaseController
{
    public function __construct(
        private AdminStampIndexUseCase $indexUseCase,
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
        return view('admin.stamps.create');
    }

    public function grantQr(string $projectKey, int $id, AdminStampGrantQrUseCase $useCase)
    {
        $output = $useCase->handle($this->resolveAdminProjectId(), $projectKey, $id);

        return view('admin.stamps.grant-qr', [
            'stampCard' => $output->stampCard,
            'stampUrl' => $output->stampUrl,
            'expiresAt' => $output->expiresAt,
        ]);
    }

    public function storeStamp(StampStoreRequest $request)
    {
        $this->storeUseCase->handle($this->resolveAdminProjectId(), $request->validated());

        return redirect()->route('admin.stamps', $this->buildAdminRouteParams())
            ->with('success', 'スタンプカードを追加しました');
    }

    public function editStamp(string $projectKey, $id)
    {
        $stampCard = $this->editUseCase->handle((int) $id);

        return view('admin.stamps.edit', compact('stampCard'));
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
