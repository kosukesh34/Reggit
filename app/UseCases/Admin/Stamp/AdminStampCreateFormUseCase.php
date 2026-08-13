<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;
use App\UseCases\Admin\Stamp\Data\AdminStampFormOutput;

class AdminStampCreateFormUseCase
{
    public function __construct(
        private StampAdminService $stampAdminService,
    ) {}

    public function handle(int $projectId): AdminStampFormOutput
    {
        return new AdminStampFormOutput(
            null,
            $this->stampAdminService->getCouponsForStampForm($projectId, null),
            $this->stampAdminService->isGachaEnabled($projectId),
        );
    }
}
