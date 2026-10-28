<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PromoPopupRequest;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupDestroyUseCase;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupDuplicateUseCase;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupFormUseCase;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupIndexUseCase;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupStoreUseCase;
use App\UseCases\Admin\PromoPopup\AdminPromoPopupUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** アプリ起動時に出す宣伝ポップアップ（全画面・モーダル・シート・バナー）。 */
class PromoPopupController extends AdminBaseController
{
    public function index(AdminPromoPopupIndexUseCase $useCase): View
    {
        return view('admin.promo-popups.index', ['popups' => $useCase->handle($this->resolveAdminProjectId())]);
    }

    public function create(AdminPromoPopupFormUseCase $useCase): View
    {
        return view('admin.promo-popups.form', ['view' => $useCase->handle($this->resolveAdminProjectId(), null)]);
    }

    public function store(PromoPopupRequest $request, AdminPromoPopupStoreUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $request->toInput());

        return redirect()->route('admin.promo-popups.index', $this->buildAdminRouteParams())
            ->with('success', 'ポップアップを作成しました');
    }

    public function duplicate(string $projectKey, int $id, AdminPromoPopupDuplicateUseCase $useCase): RedirectResponse
    {
        $copyId = $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.promo-popups.edit', [...$this->buildAdminRouteParams(), 'id' => $copyId])
            ->with('success', 'ポップアップを複製しました。内容を確認して「表示する」をオンにしてください');
    }

    public function edit(string $projectKey, int $id, AdminPromoPopupFormUseCase $useCase): View
    {
        return view('admin.promo-popups.form', ['view' => $useCase->handle($this->resolveAdminProjectId(), $id)]);
    }

    public function update(PromoPopupRequest $request, string $projectKey, int $id, AdminPromoPopupUpdateUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $id, $request->toInput());

        return redirect()->route('admin.promo-popups.index', $this->buildAdminRouteParams())
            ->with('success', 'ポップアップを更新しました');
    }

    public function destroy(string $projectKey, int $id, AdminPromoPopupDestroyUseCase $useCase): RedirectResponse
    {
        $useCase->handle($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.promo-popups.index', $this->buildAdminRouteParams())
            ->with('success', 'ポップアップを削除しました');
    }
}
