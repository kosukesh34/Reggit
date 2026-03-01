<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetProfileSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;
use Illuminate\Http\Request;

class ProfileController extends AdminBaseController
{
    private GetProfileSettingsUseCase $getProfileSettingsUseCase;

    private UpdateProfileSettingsUseCase $updateProfileSettingsUseCase;

    public function __construct(
        GetProfileSettingsUseCase $getProfileSettingsUseCase,
        UpdateProfileSettingsUseCase $updateProfileSettingsUseCase
    ) {
        $this->getProfileSettingsUseCase = $getProfileSettingsUseCase;
        $this->updateProfileSettingsUseCase = $updateProfileSettingsUseCase;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->getProfileSettingsUseCase->handle($projectId);

        return view('admin.settings.profile', compact('settings'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $request->validate([
            'smaregi_fields' => 'required|array',
            'smaregi_fields.*.enabled' => 'nullable|boolean',
            'smaregi_fields.*.required' => 'nullable|boolean',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.key' => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
            'custom_fields.*.label' => 'required|string|max:100',
            'custom_fields.*.required' => 'nullable|boolean',
        ]);
        $this->updateProfileSettingsUseCase->handle($projectId, $request);

        return redirect()->route('admin.settings.profile', $this->buildAdminRouteParams())
            ->with('success', 'プロフィール設定を更新しました');
    }
}
