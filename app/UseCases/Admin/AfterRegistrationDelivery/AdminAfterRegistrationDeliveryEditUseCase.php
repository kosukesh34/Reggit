<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryEditOutput;

class AdminAfterRegistrationDeliveryEditUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service,
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(int $projectId, int $id): AfterRegistrationDeliveryEditOutput
    {
        $delivery = $this->service->findOrFail($projectId, $id);
        $coupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);

        return new AfterRegistrationDeliveryEditOutput($delivery, $coupons);
    }
}
