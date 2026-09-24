<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\UpdateLiffDesignerRequest;
use App\UseCases\Admin\Settings\UpdateLiffDesignerUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LiffDesignerController extends AdminBaseController
{
    public function index(): RedirectResponse
    {
        return redirect()
            ->route('admin.settings.home', $this->buildAdminRouteParams())
            ->with('success', 'LINE専用の画面デザインは廃止しました。ホーム・メニューがSkipとLINEの共通画面です。');
    }

    public function update(
        UpdateLiffDesignerRequest $request,
        UpdateLiffDesignerUseCase $useCase
    ): RedirectResponse|JsonResponse {
        $useCase->handle($this->resolveAdminProjectId(), $request->toInput());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'LIFFデザインを更新しました']);
        }

        return redirect()
            ->route('admin.settings.liff-designer', $this->buildAdminRouteParams())
            ->with('success', 'LIFFデザインを更新しました');
    }
}
