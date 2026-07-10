<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\UpdateLiffDesignerRequest;
use App\UseCases\Admin\Settings\GetLiffDesignerUseCase;
use App\UseCases\Admin\Settings\UpdateLiffDesignerUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LiffDesignerController extends AdminBaseController
{
    public function index(GetLiffDesignerUseCase $useCase): View
    {
        $dto = $useCase->handle($this->resolveAdminProjectId());

        return view('admin.settings.liff-designer', [
            'layouts' => $dto->layouts,
            'templates' => $dto->templates,
            'customCss' => $dto->customCss,
            'tabSettings' => $dto->tabSettings,
            'featureFlags' => $dto->featureFlags,
            'lineFriendAddUrl' => $dto->lineFriendAddUrl,
            'menuItems' => $dto->menuItems,
        ]);
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
