<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery;

use App\Services\Admin\MemberSegmentFormOptionsService;
use App\Services\WeatherCouponService;
use App\UseCases\Admin\AfterRegistrationDelivery\Data\AfterRegistrationDeliveryFormView;

class AdminAfterRegistrationDeliveryCreateUseCase
{
    public function __construct(
        private MemberSegmentFormOptionsService $segmentFormOptions,
        private WeatherCouponService $weatherCoupons,
    ) {}

    public function handle(int $projectId): AfterRegistrationDeliveryFormView
    {
        $options = $this->segmentFormOptions->forProject($projectId);

        return new AfterRegistrationDeliveryFormView(
            $options->coupons,
            $options->rankOptions,
            null,
            $options->products,
            $options->memberTags,
            $options->rfmSegments,
            $options->profileFields,
            $options->audienceGroups,
            $this->weatherCoupons->selectableStores($projectId),
        );
    }
}
