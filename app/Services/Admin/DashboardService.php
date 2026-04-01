<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\QueueStatus;
use App\Enums\ReservationStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    public function getStats(): array
    {
        $today = today();
        $yesterday = today()->subDay();
        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $todayRevenue = Order::whereDate('created_at', $today)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');
        $yesterdayRevenue = Order::whereDate('created_at', $yesterday)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->count();

        $monthRevenue = Order::whereBetween('created_at', [$thisMonthStart, now()])
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');
        $lastMonthRevenue = Order::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_amount');

        $monthNewMembers = Member::whereBetween('created_at', [$thisMonthStart, now()])->count();
        $lastMonthNewMembers = Member::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_users' => User::count(),
            'total_members' => Member::count(),
            'today_orders' => $todayOrders,
            'today_revenue' => $todayRevenue,
            'pending_orders' => Order::where('status', OrderStatus::PENDING)->count(),
            'today_reservations' => Reservation::whereDate('reserved_at', $today)
                ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
                ->count(),
            'queue_waiting' => QueueEntry::where('status', QueueStatus::WAITING)->count(),
            'month_revenue' => $monthRevenue,
            'month_new_members' => $monthNewMembers,
            'trend_today_revenue' => $this->calcTrend($todayRevenue, $yesterdayRevenue),
            'trend_today_orders' => $this->calcTrend($todayOrders, $yesterdayOrders),
            'trend_month_revenue' => $this->calcTrend($monthRevenue, $lastMonthRevenue),
            'trend_month_members' => $this->calcTrend($monthNewMembers, $lastMonthNewMembers),
        ];
    }

    /** 前期比トレンド計算 (パーセンテージ、方向 up/down/flat) */
    private function calcTrend(float $current, float $previous): array
    {
        if ($previous === 0.0) {
            return ['direction' => $current > 0 ? 'up' : 'flat', 'percent' => null];
        }
        $percent = round((($current - $previous) / $previous) * 100, 1);
        $direction = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat');

        return ['direction' => $direction, 'percent' => abs($percent)];
    }

    public function getRecentOrders(int $limit = 10): Collection
    {
        return Order::with('items.product')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getRecentReservations(int $limit = 10): Collection
    {
        return Reservation::orderByDesc('reserved_at')
            ->limit($limit)
            ->get();
    }
}
