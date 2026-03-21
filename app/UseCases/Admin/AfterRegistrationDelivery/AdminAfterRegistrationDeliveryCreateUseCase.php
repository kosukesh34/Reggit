<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Models\Coupon;
use App\Services\Admin\MemberAdminService;
use Illuminate\Support\Collection;

class AdminAfterRegistrationDeliveryCreateUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    /** @return Collection<int, Coupon> */
    public function handle(int $projectId): Collection
    {
        return $this->memberAdminService->getActiveCouponsForSelect($projectId);
    }
}
