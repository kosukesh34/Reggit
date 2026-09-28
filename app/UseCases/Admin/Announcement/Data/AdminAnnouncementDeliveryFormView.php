<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\Coupon;
use App\Models\MemberTag;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 * @param  Collection<int, Product>  $products
 * @param  Collection<int, MemberTag>  $memberTags
 * @param  array<string, array{label: string, color: string}>  $rfmSegments
 * @param  list<array<string, mixed>>  $profileFields
 */
class AdminAnnouncementDeliveryFormView
{
    public function __construct(
        public readonly int $memberCount,
        public readonly Collection $coupons,
        public readonly Collection $products = new Collection,
        public readonly Collection $memberTags = new Collection,
        public readonly array $rfmSegments = [],
        public readonly array $profileFields = [],
    ) {}
}
