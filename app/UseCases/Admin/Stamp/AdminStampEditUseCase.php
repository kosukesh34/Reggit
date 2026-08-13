<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;
use App\UseCases\Admin\Stamp\Data\AdminStampFormOutput;

class AdminStampEditUseCase
{
    public function __construct(
        private StampAdminService $stampAdminService,
    ) {}

    public function handle(int $projectId, int $id): AdminStampFormOutput
    {
        $stampCard = $this->stampAdminService->findOrFail($id);

        return new AdminStampFormOutput(
            $stampCard,
            $this->stampAdminService->getCouponsForStampForm($projectId, $stampCard),
            $this->stampAdminService->isGachaEnabled($projectId),
        );
    }
}
