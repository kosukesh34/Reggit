<?php

namespace App\UseCases\Admin\Settings;

use App\Enums\ExistingMemberLinkField;
use App\UseCases\Admin\Settings\Data\ExistingMemberLinkPageView;

class GetExistingMemberLinkPageUseCase
{
    public function __construct(private GetExistingMemberLinkSettingUseCase $getSettingUseCase) {}

    public function handle(int $projectId, bool $canManageSettings, bool $canManageMembers): ExistingMemberLinkPageView
    {
        $setting = $this->getSettingUseCase->handle($projectId);
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
