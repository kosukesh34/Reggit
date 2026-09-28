<?php

namespace App\UseCases\Admin\Analytics;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\User;
use App\UseCases\Admin\Analytics\Data\AdminAnalyticsView;
use App\Util\AdminStatusLabels;
use App\Util\DateUtil;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

    public function handle(int $projectId, string $start, string $end): AdminAnalyticsView
    {
        $startDate = DateUtil::nullableCarbon($start)?->startOfDay();
        $endDate = DateUtil::nullableCarbon($end)?->endOfDay();
        if ($startDate === null || $endDate === null) {
            throw new InvalidArgumentException('期間が不正です');
        }

        $ordersBase = Order::where('project_id', $projectId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return new AdminAnalyticsView(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $this->buildSummary($projectId, $ordersBase, $startDate, $endDate),
            $this->buildTimeSeries($projectId, $ordersBase, $startDate, $endDate),
            $this->buildBreakdowns($projectId, $ordersBase, $startDate, $endDate),
            $this->buildCohort($projectId)
        );
    }

    /** @return array{orders: int, revenue: float, avg_order_value: float, new_members: int, reservations: int, queue: int, coupons_issued: int, coupons_used: int, points_earned: float, points_used: float, announcements_published: int, announcement_read_users: int, announcement_avg_read_rate: float} */
    private function buildSummary(int $projectId, $ordersBase, Carbon $startDate, Carbon $endDate): array
    {
        $ordersCount = (clone $ordersBase)->count();
        $revenue = (clone $ordersBase)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');
        $announcementSummary = $this->buildAnnouncementReadSummary($projectId, $startDate, $endDate);

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
            'announcements_published' => $announcementSummary['published_count'],
            'announcement_read_users' => $announcementSummary['read_users_in_period'],
            'announcement_avg_read_rate' => $announcementSummary['average_read_rate'],
        ];
    }

    /** @return array{labels: array<int, string>, orders: array<int, int>, revenue: array<int, float>, members: array<int, int>} */
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

    /** @return array{order_statuses: array, payment_methods: array, hourly_orders: array, category_sales: array, order_value_buckets: array, top_products: array, announcement_reads: array} */
    private function buildBreakdowns(int $projectId, $ordersBase, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'order_statuses' => $this->buildOrderStatusSummary($ordersBase),
            'payment_methods' => $this->buildPaymentSummary($ordersBase),
            'hourly_orders' => $this->buildHourlySummary($ordersBase),
            'category_sales' => $this->buildCategorySummary($projectId, $startDate, $endDate),
            'order_value_buckets' => $this->buildBucketSummary($ordersBase),
            'top_products' => $this->buildTopProducts($projectId, $startDate, $endDate),
            'announcement_reads' => $this->buildAnnouncementReadSummary($projectId, $startDate, $endDate),
        ];
    }

    /** @return array<int, array{status: string, label: string, count: int}> */
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

    /** @return array<int, array{method: string, label: string, count: int}> */
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

    /** @return array<int, array{category: string, label: string, quantity: int, revenue: float}> */
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

    /** @return array<int, array{product_id: int, name: string, quantity: int, revenue: float}> */
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
                'name' => $names[$row->product_id] ?? '商品情報なし',
                'quantity' => (int) $row->quantity,
                'revenue' => (float) $row->revenue,
            ];
        }

        return $result;
    }

    /**
     * 登録月コホート分析（直近6コホート × 6ヶ月後の再購買率）。
     *
     * @return array<int, array{cohort: string, total: int, month0: int, month1: int, month2: int, month3: int, month4: int, month5: int}>
     */
    private function buildCohort(int $projectId): array
    {
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $cohortStart = now()->subMonths($i)->startOfMonth();
            $cohortEnd = $cohortStart->copy()->endOfMonth();
            $memberUserIds = Member::where('project_id', $projectId)
                ->whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->all();

            $total = count($memberUserIds);
            if ($total === 0) {
                $result[] = ['cohort' => $cohortStart->format('Y/m'), 'total' => 0, 'month0' => 0, 'month1' => 0, 'month2' => 0, 'month3' => 0, 'month4' => 0, 'month5' => 0];

                continue;
            }

            $row = ['cohort' => $cohortStart->format('Y/m'), 'total' => $total];
            for ($m = 0; $m <= 5; $m++) {
                $windowStart = $cohortStart->copy()->addMonths($m)->startOfMonth();
                $windowEnd = $windowStart->copy()->endOfMonth();
                $count = Order::where('project_id', $projectId)
                    ->whereIn('user_id', $memberUserIds)
                    ->whereBetween('created_at', [$windowStart, $windowEnd])
                    ->distinct('user_id')
                    ->count('user_id');
                $row["month{$m}"] = $total > 0 ? (int) round(($count / $total) * 100) : 0;
            }
            $result[] = $row;
        }

        return $result;
    }

    /** @return array{published_count: int, total_users: int, read_users_in_period: int, average_read_rate: float, top_announcements: array<int, array{announcement_id: int, title: string, published_at: string, read_users: int, read_rate: float}>} */
    private function buildAnnouncementReadSummary(int $projectId, Carbon $startDate, Carbon $endDate): array
    {
        $announcements = Announcement::query()
            ->where('project_id', $projectId)
            ->whereBetween('published_at', [$startDate, $endDate])
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'published_at']);

        $publishedCount = $announcements->count();
        $totalUsers = User::where('project_id', $projectId)->count();
        $readUsersInPeriod = AnnouncementRead::where('project_id', $projectId)
            ->whereBetween('read_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        if ($publishedCount === 0) {
            return [
                'published_count' => 0,
                'total_users' => $totalUsers,
                'read_users_in_period' => $readUsersInPeriod,
                'average_read_rate' => 0,
                'top_announcements' => [],
            ];
        }

        $readCountByAnnouncement = AnnouncementRead::query()
            ->select('announcement_id', DB::raw('COUNT(DISTINCT user_id) as read_users'))
            ->where('project_id', $projectId)
            ->whereIn('announcement_id', $announcements->pluck('id')->all())
            ->groupBy('announcement_id')
            ->pluck('read_users', 'announcement_id');

        $top = [];
        $rateTotal = 0.0;
        foreach ($announcements as $announcement) {
            $readUsers = (int) ($readCountByAnnouncement[$announcement->id] ?? 0);
            $readRate = $totalUsers > 0 ? round(($readUsers / $totalUsers) * 100, 1) : 0.0;
            $rateTotal += $readRate;
            $top[] = [
                'announcement_id' => (int) $announcement->id,
                'title' => (string) $announcement->title,
                'published_at' => $announcement->published_at?->format('Y-m-d') ?? '',
                'read_users' => $readUsers,
                'read_rate' => $readRate,
            ];
        }

        usort($top, static function (array $a, array $b): int {
            if ($a['read_users'] === $b['read_users']) {
                return strcmp($b['published_at'], $a['published_at']);
            }

            return $b['read_users'] <=> $a['read_users'];
        });

        return [
            'published_count' => $publishedCount,
            'total_users' => $totalUsers,
            'read_users_in_period' => $readUsersInPeriod,
            'average_read_rate' => round($rateTotal / $publishedCount, 1),
            'top_announcements' => array_slice($top, 0, self::TOP_PRODUCTS_LIMIT),
        ];
    }
}
