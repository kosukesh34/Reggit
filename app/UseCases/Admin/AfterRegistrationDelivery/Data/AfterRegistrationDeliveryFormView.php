<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery\Data;

use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use Illuminate\Support\Collection;

class AfterRegistrationDeliveryFormView
{
    /**
     * @param  Collection<int, Coupon>  $coupons
     * @param  list<array{key: string, label: string}>  $rankOptions
     */
    public function __construct(
        public readonly Collection $coupons,
        public readonly array $rankOptions,
        public readonly ?AfterRegistrationDelivery $delivery = null,
    ) {}
}
