<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\SmaregiContract;
use App\Util\StrHelper;

class UpdateSmaregiLinkUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId, ?string $contractId): void
    {
        $trimmed = $contractId !== null ? trim($contractId) : '';

        if (StrHelper::isEmptyStringOrNull($trimmed)) {
            $this->shopSettingRepository->setValue('smaregi_contract_id', '', 'string', $projectId);
            SmaregiContract::where('project_id', $projectId)->update(['project_id' => null]);

            return;
        }

        $this->shopSettingRepository->setValue('smaregi_contract_id', $trimmed, 'string', $projectId);
        SmaregiContract::where('project_id', $projectId)->where('contract_id', '!=', $trimmed)->update(['project_id' => null]);
        SmaregiContract::where('contract_id', $trimmed)->update(['project_id' => $projectId]);
    }
}
