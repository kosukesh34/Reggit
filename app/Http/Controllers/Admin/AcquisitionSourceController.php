<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AcquisitionSourceRequest;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceDestroyUseCase;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceStoreUseCase;
use Illuminate\Http\RedirectResponse;

/** 流入経路ごとの登録QRの追加・削除。一覧は QRコード発行画面に出す。 */
class AcquisitionSourceController extends AdminBaseController
{
    public function store(AcquisitionSourceRequest $request, AdminAcquisitionSourceStoreUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), (string) $request->validated('name'), (string) ($request->validated('landing_tab') ?? 'home'));

        return redirect()->route('admin.qr-codes', $this->buildAdminRouteParams())
            ->with('success', '流入経路を追加しました');
    }

    public function destroy(string $projectKey, int $id, AdminAcquisitionSourceDestroyUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.qr-codes', $this->buildAdminRouteParams())
            ->with('success', '流入経路を削除しました');
    }
}
