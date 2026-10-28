<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AcquisitionSourceRequest;
use App\Support\AppScreenCatalog;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceDestroyUseCase;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceIndexUseCase;
use App\UseCases\Admin\AcquisitionSource\AdminAcquisitionSourceStoreUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** 流入経路ごとの登録QRと、経路別の登録数。 */
class AcquisitionSourceController extends AdminBaseController
{
    public function index(AdminAcquisitionSourceIndexUseCase $useCase): View
    {
        return view('admin.acquisition-sources', ['sources' => $useCase->handle($this->resolveAdminProjectId(), $this->resolveAdminProjectKey()), 'screens' => AppScreenCatalog::tabs()]);
    }

    public function store(AcquisitionSourceRequest $request, AdminAcquisitionSourceStoreUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), (string) $request->validated('name'), (string) ($request->validated('landing_tab') ?? 'home'));

        return redirect()->route('admin.acquisition-sources', $this->buildAdminRouteParams())
            ->with('success', '流入経路を追加しました');
    }

    public function destroy(string $projectKey, int $id, AdminAcquisitionSourceDestroyUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.acquisition-sources', $this->buildAdminRouteParams())
            ->with('success', '流入経路を削除しました');
    }
}
