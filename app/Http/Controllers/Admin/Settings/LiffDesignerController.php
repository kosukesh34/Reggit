<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;
use App\UseCases\Admin\Settings\GetLiffDesignerUseCase;
use App\UseCases\Admin\Settings\UpdateLiffDesignerUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'liffId' => $dto->liffId,
            'liffUrl' => $dto->liffUrl,
            'lineFriendAddUrl' => $dto->lineFriendAddUrl,
        ]);
    }

    public function update(
        Request $request,
        UpdateLiffDesignerUseCase $useCase
    ): RedirectResponse {
        $input = UpdateLiffDesignerInput::fromRequest($request);
        $useCase->handle($this->resolveAdminProjectId(), $input);

        return redirect()
            ->route('admin.settings.liff-designer', $this->buildAdminRouteParams())
            ->with('success', 'LIFFデザインを更新しました');
    }
}
