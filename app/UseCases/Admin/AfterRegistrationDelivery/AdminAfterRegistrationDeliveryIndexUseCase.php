<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Models\AfterRegistrationDelivery;
use App\Services\Admin\AfterRegistrationDeliveryAdminService;
use Illuminate\Support\Collection;

class AdminAfterRegistrationDeliveryIndexUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service
    ) {}

    /** @return Collection<int, AfterRegistrationDelivery> */
    public function handle(int $projectId): Collection
    {
        return $this->service->getListByProject($projectId);
    }
}
