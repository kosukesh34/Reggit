<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery\Data;

use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use Illuminate\Support\Collection;

class AfterRegistrationDeliveryEditOutput
{
    /**
     * @param Collection<int, Coupon> $coupons
     */
    public function __construct(
        public AfterRegistrationDelivery $delivery,
        public Collection $coupons
    ) {}
}
