<?php

namespace App\Support;

use App\Enums\ProjectPlan;

class PlanFeatures
{
    public const ORDERS = 'orders';

    public const MEMBERS = 'members';

    public const RESERVATIONS = 'reservations';

    public const QUEUE = 'queue';

    public const STAMPS = 'stamps';

    public const COUPONS = 'coupons';

    public const COUPON_DELIVERY_SCHEDULED = 'coupon_delivery_scheduled';

    public const ANNOUNCEMENT_DELIVERY = 'announcement_delivery';

    public const CRM = 'crm';

    public const LOTTERY = 'lottery';

    public const WALLET = 'wallet';

    public const ANALYTICS_ADVANCED = 'analytics_advanced';

    public const LIFF_DESIGNER_ADVANCED = 'liff_designer_advanced';

    /** @var array<string, list<string>> */
    private const TIER_FEATURES = [
        ProjectPlan::NORMAL => [
            self::ORDERS,
            self::MEMBERS,
            self::RESERVATIONS,
            self::QUEUE,
            self::STAMPS,
            self::COUPONS,
        ],
        ProjectPlan::DISTRIBUTION => [
            self::COUPON_DELIVERY_SCHEDULED,
            self::ANNOUNCEMENT_DELIVERY,
            self::CRM,
        ],
        ProjectPlan::PREMIUM => [
            self::LOTTERY,
            self::WALLET,
            self::ANALYTICS_ADVANCED,
            self::LIFF_DESIGNER_ADVANCED,
        ],
    ];

    /** @var list<string> */
    private const PLAN_TIERS = [
        ProjectPlan::NORMAL,
        ProjectPlan::DISTRIBUTION,
        ProjectPlan::PREMIUM,
    ];

    public static function hasFeature(string $plan, string $feature): bool
    {
        $planIndex = self::resolvePlanIndex($plan);

        for ($i = 0; $i <= $planIndex; $i++) {
            $tier = self::PLAN_TIERS[$i];
            if (in_array($feature, self::TIER_FEATURES[$tier], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function featuresForPlan(string $plan): array
    {
        $planIndex = self::resolvePlanIndex($plan);
        $features = [];

        for ($i = 0; $i <= $planIndex; $i++) {
            $features = array_merge($features, self::TIER_FEATURES[self::PLAN_TIERS[$i]]);
        }

        return array_values(array_unique($features));
    }

    private static function resolvePlanIndex(string $plan): int
    {
        $planIndex = array_search($plan, self::PLAN_TIERS, true);

        return $planIndex === false ? 0 : $planIndex;
    }
}
