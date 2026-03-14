<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;

class AdminAfterRegistrationDeliveryDestroyUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    public function handle(int $projectId, int $id): void
    {
        $this->service->delete($projectId, $id);
    }
}
