<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 * @param  Collection<int, \App\Models\Product>  $products
 */
class AdminAnnouncementDeliveryFormView
{
    public function __construct(
        public readonly int $memberCount,
        public readonly Collection $coupons,
        public readonly Collection $products = new Collection,
    ) {}
}
