<?php

namespace App\UseCases\Admin\Settings;

use App\Enums\ExistingMemberLinkField;
use App\Models\ExistingMemberLinkSetting;
use App\UseCases\Admin\Settings\Data\ExistingMemberLinkPageView;

class GetExistingMemberLinkPageUseCase
{
    public function handle(int $projectId, bool $canManageSettings, bool $canManageMembers): ExistingMemberLinkPageView
    {
        $setting = ExistingMemberLinkSetting::query()->where('project_id', $projectId)->first();
        $selected = is_array($setting?->identifier_fields)
            ? $setting->identifier_fields
            : [ExistingMemberLinkField::CUSTOMER_CODE];

        $fields = [];
        foreach (ExistingMemberLinkField::getValues() as $key => $label) {
            $fields[] = [
                'key' => (string) $key,
                'label' => $label,
                'isChecked' => in_array($key, $selected, true),
            ];
        }

        return new ExistingMemberLinkPageView(
            $setting?->is_enabled === true,
            (string) ($setting?->description ?? ''),
            $fields,
            $canManageSettings,
            $canManageMembers,
        );
    }
}
