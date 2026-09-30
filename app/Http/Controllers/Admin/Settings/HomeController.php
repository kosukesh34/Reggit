<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Requests\Admin\SettingsHomeRequest;
use App\UseCases\Admin\Settings\GetHomeSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateHomeSettingsUseCase;

class HomeController extends AdminBaseController
{
    private GetHomeSettingsUseCase $getHomeSettingsUseCase;

    private UpdateHomeSettingsUseCase $updateHomeSettingsUseCase;

    public function __construct(
        GetHomeSettingsUseCase $getHomeSettingsUseCase,
        UpdateHomeSettingsUseCase $updateHomeSettingsUseCase,
    ) {
        $this->getHomeSettingsUseCase = $getHomeSettingsUseCase;
        $this->updateHomeSettingsUseCase = $updateHomeSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $data = $this->getHomeSettingsUseCase->handle($projectId);

        return view('admin.settings.home', $data);
    }

    public function update(SettingsHomeRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        try {
            $this->updateHomeSettingsUseCase->handle($request->toInput($projectId));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['home_images' => $e->getMessage()])->withInput();
        }

        $url = route('admin.settings.home', $this->buildAdminRouteParams());
        if (isset($validated['design_tab'])) {
            $url .= '#design-'.$validated['design_tab'];
        }

        return redirect()->to($url)
            ->with('success', 'ホーム設定を更新しました');
    }
}
