<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Enums\ExistingMemberLinkField;
use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Settings\GetExistingMemberLinkPageUseCase;
use App\UseCases\Admin\Settings\GetExistingMemberLinkSettingUseCase;
use App\UseCases\Admin\Settings\UpdateExistingMemberLinkSettingsUseCase;
use App\UseCases\Member\RequestExistingMemberLinkUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ExistingMemberLinkController extends AdminBaseController
{
    public function __construct(
        private RequestExistingMemberLinkUseCase $requestUseCase,
        private GetExistingMemberLinkPageUseCase $getPageUseCase,
        private GetExistingMemberLinkSettingUseCase $getSettingUseCase,
        private UpdateExistingMemberLinkSettingsUseCase $updateSettingsUseCase,
    ) {}

    public function index(): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin !== null && $admin->hasAnyPermission(['settings.view', 'members.manage']), 403);

        return view('admin.settings.existing-member-link', [
            'view' => $this->getPageUseCase->handle(
                $this->resolveAdminProjectId(),
                $admin->hasPermission('settings.manage'),
                $admin->hasPermission('members.manage'),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->hasPermission('settings.manage') === true, 403);
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:300'],
            'identifier_fields' => ['required', 'array', 'min:1'],
            'identifier_fields.*' => ['string', Rule::in(array_keys(ExistingMemberLinkField::getValues()))],
        ]);
        $this->updateSettingsUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->boolean('is_enabled'),
            $validated['description'] ?? null,
            array_values($validated['identifier_fields']),
        );

        return redirect()->route('admin.settings.existing-member-link', $this->buildAdminRouteParams())
            ->with('success', '既存会員連携の設定を保存しました');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->hasPermission('members.manage') === true, 403);
        $projectId = $this->resolveAdminProjectId();
        $setting = $this->getSettingUseCase->handle($projectId);
        if ($setting === null || $setting->is_enabled === false) {
            return back()->with('error', '既存会員連携は利用できません');
        }
        $rules = [];
        foreach ($setting->identifier_fields as $field) {
            $rules[$field] = $field === ExistingMemberLinkField::BIRTHDAY
                ? ['required', 'date']
                : ['required', 'string', 'max:255'];
        }
        $validated = $request->validate($rules);
        $input = [];
        foreach ($setting->identifier_fields as $field) {
            $input[$field] = (string) $validated[$field];
        }

        try {
            $result = $this->requestUseCase->handle($projectId, null, $input);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.settings.existing-member-link', $this->buildAdminRouteParams())
            ->with('success', $result['message']);
    }
}
