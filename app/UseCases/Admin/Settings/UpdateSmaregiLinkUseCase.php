<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Models\SmaregiContract;
use App\Util\StrHelper;

class UpdateSmaregiLinkUseCase
{
    public function handle(int $projectId, ?string $contractId): void
    {
        $trimmed = $contractId !== null ? trim($contractId) : '';

        if (StrHelper::isEmptyStringOrNull($trimmed)) {
            ShopSetting::setValue('smaregi_contract_id', '', 'string', $projectId);
            SmaregiContract::where('project_id', $projectId)->update(['project_id' => null]);
            return;
        }

        ShopSetting::setValue('smaregi_contract_id', $trimmed, 'string', $projectId);
        SmaregiContract::where('project_id', $projectId)->where('contract_id', '!=', $trimmed)->update(['project_id' => null]);
        SmaregiContract::where('contract_id', $trimmed)->update(['project_id' => $projectId]);
    }
}
