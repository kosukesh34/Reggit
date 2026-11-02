<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;

class AdminAfterRegistrationDeliveryActivationUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    public function handle(int $projectId, int $id, bool $active): void
    {
        $this->service->setActive($projectId, $id, $active);
    }
}
