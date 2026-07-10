<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * お知らせ一斉配信フォーム画面用データ。
 *
 * @param  Collection<int, Coupon>  $coupons
 */
class AdminAnnouncementDeliveryFormView
{
    public function __construct(
        public readonly int $memberCount,
        public readonly Collection $coupons,
    ) {}
}
