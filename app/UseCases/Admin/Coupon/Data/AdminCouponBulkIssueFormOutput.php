<?php

namespace App\UseCases\Admin\Coupon\Data;

use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use App\Models\StampCard;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 * @param  Collection<int, CouponDelivery>  $scheduledDeliveries
 * @param  list<array{
 *     id: int,
 *     coupon_name: string,
 *     coupon_code: string,
 *     delivery_type_label: string,
 *     status: string,
 *     status_label: string,
 *     status_badge: string,
 *     timing_label: string,
 *     issued_count_label: string,
 *     recipients_label: string,
 *     recipients_title: string
 * }>  $deliveryHistories
 * @param  array<string, string>  $deliveryTypeOptions
 * @param  array<string, array{label: string, icon: string, types: list<string>}>  $deliveryGroups
 * @param  array{
 *     scheduledTypes: list<string>,
 *     personalTypes: list<string>,
 *     segmentTypes: list<string>,
 *     birthdayTypes: list<string>,
 *     newMemberTypes: list<string>,
 *     inactiveTypes: list<string>
 * }  $deliveryTypeJsConfig
 * @param  Collection<int, Product>  $products
 * @param  array<int, int>  $deliveryCountByCouponId
 * @param  Collection<int, StampCard>  $stampCards
 * @param  list<array{key: string, label: string}>  $rankOptions
 * @param  string  $defaultDeliveryType
 * @param  Collection<int, MemberTag>  $memberTags
 * @param  array<string, array{label: string, color: string}>  $rfmSegments
 * @param  list<array<string, scalar|null>>  $profileFields
 * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
 */
class AdminCouponBulkIssueFormOutput
{
    public function __construct(
        public Collection $coupons,
        public int $memberCount,
        public bool $hasSmaregiContract = false,
        public int $smaregiLinkedCount = 0,
        public int $smaregiUnlinkedCount = 0,
        public Collection $scheduledDeliveries = new Collection,
        public array $deliveryHistories = [],
        public array $deliveryTypeOptions = [],
        public array $deliveryGroups = [],
        public array $deliveryTypeJsConfig = [],
        public Collection $products = new Collection,
        public array $deliveryCountByCouponId = [],
        public Collection $stampCards = new Collection,
        public array $rankOptions = [],
        public string $defaultDeliveryType = '',
        public array $rfmSegments = [],
        public Collection $memberTags = new Collection,
        public array $profileFields = [],
        public Collection $audienceGroups = new Collection,
    ) {}
}
