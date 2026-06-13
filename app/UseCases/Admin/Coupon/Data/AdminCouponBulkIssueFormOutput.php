<?php

namespace App\UseCases\Admin\Coupon\Data;

use App\Models\Coupon;
use App\Models\CouponDelivery;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 * @param  Collection<int, CouponDelivery>  $scheduledDeliveries
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
        public array $deliveryTypeOptions = [],
        public array $deliveryGroups = [],
        public array $deliveryTypeJsConfig = [],
    ) {}
}
