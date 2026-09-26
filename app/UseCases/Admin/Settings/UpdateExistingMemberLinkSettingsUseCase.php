<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ExistingMemberLinkSetting;

class UpdateExistingMemberLinkSettingsUseCase
{
    /** @param list<string> $identifierFields */
    public function handle(
        int $projectId,
        bool $isEnabled,
        ?string $description,
        array $identifierFields,
    ): ExistingMemberLinkSetting {
        return ExistingMemberLinkSetting::query()->updateOrCreate(
            ['project_id' => $projectId],
            [
                'is_enabled' => $isEnabled,
                'description' => $description,
                'identifier_fields' => $identifierFields,
            ],
        );
    }
}
