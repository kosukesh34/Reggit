<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;

class AdminAfterRegistrationDeliveryUpdateUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, int $id, array $validated): void
    {
        $this->service->update($projectId, $id, $validated);
    }
}
