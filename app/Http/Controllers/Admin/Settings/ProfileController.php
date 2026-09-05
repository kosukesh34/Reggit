<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Domain\Settings\ShopSettingKeys;
use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Support\AdminSettingsInputFactory;
use App\Services\Smaregi\SmaregiCustomerCustomFieldLabelSyncService;
use App\UseCases\Admin\Settings\GetProfileSettingsUseCase;
use App\UseCases\Admin\Settings\UpdateProfileSettingsUseCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends AdminBaseController
{
    private GetProfileSettingsUseCase $getProfileSettingsUseCase;

    private UpdateProfileSettingsUseCase $updateProfileSettingsUseCase;

    private AdminSettingsInputFactory $inputFactory;

    public function __construct(
        GetProfileSettingsUseCase $getProfileSettingsUseCase,
        UpdateProfileSettingsUseCase $updateProfileSettingsUseCase,
        AdminSettingsInputFactory $inputFactory
    ) {
        $this->getProfileSettingsUseCase = $getProfileSettingsUseCase;
        $this->updateProfileSettingsUseCase = $updateProfileSettingsUseCase;
        $this->inputFactory = $inputFactory;
    }

    public function index()
    {
        $projectId = $this->resolveAdminProjectId();
        $view = $this->getProfileSettingsUseCase->handle($projectId);

        return view('admin.settings.profile', ['view' => $view]);
    }

    public function update(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $request->validate([
            'member_registration_mode' => ['required', 'string', Rule::in(array_keys(MemberRegistrationMode::getValues()))],
            'smaregi_fields' => 'required|array',
            'smaregi_fields.*.enabled' => 'nullable|boolean',
            'smaregi_fields.*.required' => 'nullable|boolean',
            'custom_fields' => 'nullable|array|max:'.ShopSettingKeys::PROFILE_CUSTOM_FIELD_MAX,
            'custom_fields.*.key' => 'nullable|string|max:64|regex:/^[a-z0-9_]+$/',
            'custom_fields.*.label' => 'required|string|max:'.SmaregiCustomerCustomFieldLabelSyncService::LABEL_MAX,
            'custom_fields.*.required' => 'nullable|boolean',
            'custom_fields.*.input_type' => ['nullable', 'string', Rule::in(array_keys(ProfileCustomFieldInputType::getValues()))],
            'custom_fields.*.values' => 'nullable',
            'custom_fields.*.description' => 'nullable|string|max:255',
            'custom_fields.*.smaregi_no' => 'nullable|integer|min:1',
        ]);
        $this->updateProfileSettingsUseCase->handle(
            $this->inputFactory->profileSettings($projectId, $request),
        );

        return redirect()->route('admin.settings.profile', $this->buildAdminRouteParams())
            ->with('success', 'プロフィール設定を更新しました');
    }
}
