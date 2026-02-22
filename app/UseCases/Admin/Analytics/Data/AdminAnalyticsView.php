<?php

namespace App\UseCases\Admin\Analytics\Data;

/**
 * 管理画面・分析の表示用データ。不変。
 * @param array{orders: int, revenue: float, avg_order_value: float, new_members: int, reservations: int, queue: int, coupons_issued: int, coupons_used: int, points_earned: float, points_used: float} $summary
 * @param array{labels: array<int, string>, orders: array<int, int>, revenue: array<int, float>, members: array<int, int>} $chart
 * @param array{order_statuses: array, payment_methods: array, hourly_orders: array, category_sales: array, order_value_buckets: array, top_products: array} $breakdowns
 */
class AdminAnalyticsView
{
    public function __construct(
        public string $start,
        public string $end,
        public array $summary,
        public array $chart,
        public array $breakdowns
    ) {
    }
}
