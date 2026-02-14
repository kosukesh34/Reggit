<?php

namespace App\UseCases\Admin\Analytics;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\UseCases\Admin\Analytics\Dto\AdminAnalyticsViewDto;
use App\Util\AdminStatusLabels;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsUseCase
{
    private const ORDER_VALUE_BUCKETS = [
        ['min' => 0, 'max' => 999, 'label' => '0-999'],
        ['min' => 1000, 'max' => 1999, 'label' => '1000-1999'],
        ['min' => 2000, 'max' => 2999, 'label' => '2000-2999'],
        ['min' => 3000, 'max' => 4999, 'label' => '3000-4999'],
        ['min' => 5000, 'max' => null, 'label' => '5000+'],
    ];

    private const CATEGORY_LABELS = [
        'food' => 'フード',
        'drink' => 'ドリンク',
        'dessert' => 'デザート',
        'side' => 'サイドメニュー',
        'other' => 'その他',
    ];

    private const TOP_PRODUCTS_LIMIT = 5;

    public function handle(int $projectId, string $start, string $end): AdminAnalyticsViewDto
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        $ordersBase = Order::where('project_id', $projectId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return new AdminAnalyticsViewDto(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $this->buildSummary($projectId, $ordersBase, $startDate, $endDate),
            $this->buildTimeSeries($projectId, $ordersBase, $startDate, $endDate),
            $this->buildBreakdowns($projectId, $ordersBase, $startDate, $endDate)
        );
    }

    /** @return array<string, mixed> */
    private function buildSummary(int $projectId, $ordersBase, Carbon $startDate, Carbon $endDate): array
    {
        $ordersCount = (clone $ordersBase)->count();
        $revenue = (clone $ordersBase)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');

        return [
            'orders' => $ordersCount,
            'revenue' => $revenue,
            'avg_order_value' => $ordersCount > 0 ? ($revenue / $ordersCount) : 0,
            'new_members' => Member::where('project_id', $projectId)
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'reservations' => Reservation::where('project_id', $projectId)
                ->whereBetween('reserved_at', [$startDate, $endDate])->count(),
            'queue' => QueueEntry::where('project_id', $projectId)
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'coupons_issued' => MemberCoupon::where('project_id', $projectId)
                ->whereBetween('issued_at', [$startDate, $endDate])->count(),
            'coupons_used' => CouponRedemption::where('project_id', $projectId)
                ->whereBetween('used_at', [$startDate, $endDate])->count(),
            'points_earned' => PointTransaction::where('project_id', $projectId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', PointTransactionType::EARNED)->sum('points'),
            'points_used' => abs(PointTransaction::where('project_id', $projectId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('type', PointTransactionType::USED)->sum('points')),
        ];
    }

    /** @return array<string, mixed> */
    private function buildTimeSeries(int $projectId, $ordersBase, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $ordersByDate = [];
        $revenueByDate = [];
        $membersByDate = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $key;
            $ordersByDate[$key] = 0;
            $revenueByDate[$key] = 0;
            $membersByDate[$key] = 0;
        }

        $this->fillOrderTimeSeries($ordersBase, $ordersByDate, $revenueByDate);
        $this->fillMemberTimeSeries($projectId, $startDate, $endDate, $membersByDate);

        return [
            'labels' => $labels,
            'orders' => array_values($ordersByDate),
            'revenue' => array_values($revenueByDate),
            'members' => array_values($membersByDate),
        ];
    }

    private function fillOrderTimeSeries($ordersBase, array &$ordersByDate, array &$revenueByDate): void
    {
        $cancelledStatus = OrderStatus::CANCELLED;
        $rows = (clone $ordersBase)->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw("SUM(CASE WHEN status != \"{$cancelledStatus}\" THEN total_amount ELSE 0 END) as revenue")
        )->groupBy(DB::raw('DATE(created_at)'))->get();

        foreach ($rows as $row) {
            if (array_key_exists($row->date, $ordersByDate)) {
                $ordersByDate[$row->date] = (int) $row->count;
                $revenueByDate[$row->date] = (float) $row->revenue;
            }
        }
    }

    private function fillMemberTimeSeries(int $projectId, Carbon $startDate, Carbon $endDate, array &$membersByDate): void
    {
        $rows = Member::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        foreach ($rows as $row) {
            if (array_key_exists($row->date, $membersByDate)) {
                $membersByDate[$row->date] = (int) $row->count;
            }
        }
    }

    /** @return array<string, mixed> */
    private function buildBreakdowns(int $projectId, $ordersBase, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'order_statuses' => $this->buildOrderStatusSummary($ordersBase),
            'payment_methods' => $this->buildPaymentSummary($ordersBase),
            'hourly_orders' => $this->buildHourlySummary($ordersBase),
            'category_sales' => $this->buildCategorySummary($projectId, $startDate, $endDate),
            'order_value_buckets' => $this->buildBucketSummary($ordersBase),
            'top_products' => $this->buildTopProducts($projectId, $startDate, $endDate),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function buildOrderStatusSummary($ordersBase): array
    {
        $result = [];
        $rows = (clone $ordersBase)->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->get();
        foreach ($rows as $row) {
            $result[] = [
                'status' => $row->status,
                'label' => AdminStatusLabels::orderStatusLabel($row->status),
                'count' => (int) $row->count,
            ];
        }
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildPaymentSummary($ordersBase): array
    {
        $result = [];
        $rows = (clone $ordersBase)->select('payment_method', DB::raw('COUNT(*) as count'))->groupBy('payment_method')->get();
        foreach ($rows as $row) {
            $result[] = [
                'method' => $row->payment_method,
                'label' => PaymentMethod::getLabel((string) $row->payment_method),
                'count' => (int) $row->count,
            ];
        }
        return $result;
    }

    /** @return array<int, int> */
    private function buildHourlySummary($ordersBase): array
    {
        $hourly = array_fill(0, 24, 0);
        $rows = (clone $ordersBase)->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))->get();
        foreach ($rows as $row) {
            $hourly[(int) $row->hour] = (int) $row->count;
        }
        return $hourly;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildCategorySummary(int $projectId, Carbon $startDate, Carbon $endDate): array
    {
        $rows = OrderItem::query()
            ->select('products.category', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.project_id', $projectId)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', OrderStatus::CANCELLED)
            ->groupBy('products.category')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $key = (string) $row->category;
            $result[] = [
                'category' => $key,
                'label' => self::CATEGORY_LABELS[$key] ?? $key,
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ];
        }
        return $result;
    }

    /** @return array<string, int> */
    private function buildBucketSummary($ordersBase): array
    {
        $buckets = [];
        foreach (self::ORDER_VALUE_BUCKETS as $bucket) {
            $buckets[$bucket['label']] = 0;
        }

        $totals = (clone $ordersBase)->where('status', '!=', OrderStatus::CANCELLED)->pluck('total_amount')->all();
        foreach ($totals as $total) {
            $value = (float) $total;
            foreach (self::ORDER_VALUE_BUCKETS as $bucket) {
                if ($value >= $bucket['min'] && ($bucket['max'] === null || $value <= $bucket['max'])) {
                    $buckets[$bucket['label']]++;
                    break;
                }
            }
        }
        return $buckets;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildTopProducts(int $projectId, Carbon $startDate, Carbon $endDate): array
    {
        $rows = OrderItem::query()
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.project_id', $projectId)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', OrderStatus::CANCELLED)
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(self::TOP_PRODUCTS_LIMIT)
            ->get();

        $names = Product::whereIn('id', $rows->pluck('product_id')->all())->pluck('name', 'id');
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'product_id' => (int) $row->product_id,
                'name' => $names[$row->product_id] ?? 'N/A',
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ];
        }
        return $result;
    }
}
