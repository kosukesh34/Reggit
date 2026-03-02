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
        return [
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_users' => User::count(),
            'total_members' => Member::count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->where('status', '!=', OrderStatus::CANCELLED)
                ->sum('total_amount'),
            'pending_orders' => Order::where('status', OrderStatus::PENDING)->count(),
            'today_reservations' => Reservation::whereDate('reserved_at', today())
                ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
                ->count(),
            'queue_waiting' => QueueEntry::where('status', QueueStatus::WAITING)->count(),
        ];
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
