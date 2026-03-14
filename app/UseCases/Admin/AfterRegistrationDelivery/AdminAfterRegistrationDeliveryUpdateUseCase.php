<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;

class AdminAfterRegistrationDeliveryUpdateUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    /** @param array{delay_minutes: string, title: string, body?: string, coupon_id?: string, is_active?: string} $validated */
    public function handle(int $projectId, int $id, array $validated): void
    {
        $this->service->update($projectId, $id, $validated);
    }
}
