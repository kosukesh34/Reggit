<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Models\AfterRegistrationDelivery;
use App\Services\Admin\AfterRegistrationDeliveryAdminService;

class AdminAfterRegistrationDeliveryStoreUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    /** @param array{delay_minutes: string, title: string, body?: string, coupon_id?: string, is_active?: string} $validated */
    public function handle(int $projectId, array $validated): AfterRegistrationDelivery
    {
        return $this->service->create($projectId, $validated);
    }
}
