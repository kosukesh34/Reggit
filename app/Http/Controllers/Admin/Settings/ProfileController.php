<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\MemberRegistrationMode;
use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Support\AdminSettingsInputFactory;
use App\UseCases\Admin\Settings\GetProfileSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends AdminBaseController
{
    public function __construct(
        private GetProfileSettingsUseCase $getProfileSettingsUseCase,
        private UpdateProfileSettingsUseCase $updateProfileSettingsUseCase,
        private AdminSettingsInputFactory $inputFactory,
    ) {}

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $settings = $this->getProfileSettingsUseCase->handle($projectId);
        $memberRegistrationModeOptions = MemberRegistrationMode::getValues();

        return view('admin.settings.profile', compact('settings', 'memberRegistrationModeOptions'));
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $request->validate([
            'member_registration_mode' => ['required', 'string', Rule::in(array_keys(MemberRegistrationMode::getValues()))],
            'smaregi_fields' => 'required|array',
            'smaregi_fields.*.enabled' => 'nullable|boolean',
            'smaregi_fields.*.required' => 'nullable|boolean',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.key' => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
            'custom_fields.*.label' => 'required|string|max:100',
            'custom_fields.*.required' => 'nullable|boolean',
        ]);
        $this->updateProfileSettingsUseCase->handle(
            $this->inputFactory->profileSettings($projectId, $request),
        );

        return redirect()->route('admin.settings.profile', $this->buildAdminRouteParams())
            ->with('success', 'プロフィール設定を更新しました');
    }
}
