<?php

namespace App\UseCases\Admin\AfterRegistrationDelivery\Data;

use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;

class AfterRegistrationDeliveryFormView
{
    /**
     * @param  Collection<int, Coupon>  $coupons
     * @param  list<array{key: string, label: string}>  $rankOptions
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, MemberTag>  $memberTags
     * @param  array<string, array{label: string, color: string}>  $rfmSegments
     * @param  list<array<string, mixed>>  $profileFields
     * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
     * @param  Collection<int, Store>  $weatherStores  雨の日で選べる店舗
     */
    public function __construct(
        public readonly Collection $coupons,
        public readonly array $rankOptions,
        public readonly ?AfterRegistrationDelivery $delivery = null,
        public readonly Collection $products = new Collection,
        public readonly Collection $memberTags = new Collection,
        public readonly array $rfmSegments = [],
        public readonly array $profileFields = [],
        public readonly Collection $audienceGroups = new Collection,
        public readonly Collection $weatherStores = new Collection,
    ) {}
}
