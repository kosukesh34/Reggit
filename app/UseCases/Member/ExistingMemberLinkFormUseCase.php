<?php

namespace App\UseCases\Member;

use App\Enums\ExistingMemberLinkField;
use App\Models\ExistingMemberLinkSetting;

/** 既存会員連携の入力フォーム（有効かどうか・入力する項目）。 */
class ExistingMemberLinkFormUseCase
{
    /** @return array{is_enabled: bool, description: string, fields: list<array{key: string, label: string}>} */
    public function form(int $projectId): array
    {
        $setting = ExistingMemberLinkSetting::query()->where('project_id', $projectId)->first();
        $selected = is_array($setting?->identifier_fields) ? $setting->identifier_fields : [];
        $fields = [];
        foreach (ExistingMemberLinkField::getValues() as $key => $label) {
            if (in_array($key, $selected, true)) {
                $fields[] = ['key' => (string) $key, 'label' => $label];
            }
        }

        return [
            'is_enabled' => $setting?->is_enabled === true,
            'description' => (string) ($setting?->description ?? ''),
            'fields' => $fields,
        ];
    }

    /** @return list<string>|null 連携が無効なら null */
    public function identifierFields(int $projectId): ?array
    {
        $setting = ExistingMemberLinkSetting::query()->where('project_id', $projectId)->first();
        if ($setting === null || $setting->is_enabled === false) {
            return null;
        }

        return $setting->identifier_fields;
    }
}
