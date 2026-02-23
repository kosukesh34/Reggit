<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Support\LiffDesignerSanitizer;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;
use App\UseCases\Admin\Settings\GetLiffDesignerUseCase;
use App\UseCases\Admin\Settings\UpdateLiffDesignerUseCase;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LiffDesignerController extends AdminBaseController
{
    public function index(GetLiffDesignerUseCase $useCase): \Illuminate\View\View
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
        ]);
    }

    public function update(
        Request $request,
        UpdateLiffDesignerUseCase $useCase
    ): \Illuminate\Http\RedirectResponse {
        $layoutsJson = $request->input('layouts_json');
        $decoded = json_decode($layoutsJson, true);

        if (is_array($decoded) === false) {
            return redirect()->back()
                ->withErrors(['layouts' => 'レイアウトが不正です']);
        }

        $tabSettings = null;
        $tabSettingsJson = $request->input('tab_settings_json');
        if ($tabSettingsJson !== null && $tabSettingsJson !== '') {
            $parsed = json_decode($tabSettingsJson, true);
            if (is_array($parsed)) {
                $tabSettings = $parsed;
            }
        }

        try {
            $input = new UpdateLiffDesignerInput(
                LiffDesignerSanitizer::sanitizeLayouts($decoded),
                LiffDesignerSanitizer::sanitizeCustomCss((string) $request->input('custom_css', '')),
                LiffDesignerSanitizer::sanitizeTabSettings($tabSettings),
                trim((string) $request->input('line_liff_id', '')),
                trim((string) $request->input('line_liff_url', ''))
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['liff_designer' => $e->getMessage()]);
        }
        $useCase->handle($this->resolveAdminProjectId(), $input);

        return redirect()
            ->route('admin.settings.liff-designer', $this->buildAdminRouteParams())
            ->with('success', 'LIFFデザインを更新しました');
    }
}
