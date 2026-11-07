<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\AfterRegistrationDeliveryAdminService;
use App\Services\Admin\MemberSegmentFormOptionsService;
use App\Services\WeatherCouponService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryFormView;

class AdminAfterRegistrationDeliveryEditUseCase
{
    public function __construct(
        private AfterRegistrationDeliveryAdminService $service,
        private MemberSegmentFormOptionsService $segmentFormOptions,
        private WeatherCouponService $weatherCoupons,
    ) {}

    public function handle(int $projectId, int $id): AfterRegistrationDeliveryFormView
    {
        $options = $this->segmentFormOptions->forProject($projectId);

        return new AfterRegistrationDeliveryFormView(
            $options->coupons,
            $options->rankOptions,
            $this->service->findOrFail($projectId, $id),
            $options->products,
            $options->memberTags,
            $options->rfmSegments,
            $options->profileFields,
            $options->audienceGroups,
            $this->weatherCoupons->selectableStores($projectId),
        );
    }
}
