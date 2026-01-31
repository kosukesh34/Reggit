<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\Enums\ReservationStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private const RESERVATION_CAPACITY_MIN = 1;

    public function dashboard()
    {
        $stats = [
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_users' => User::count(),
            'total_members' => Member::count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];

        $recent_orders = Order::with('user', 'items.product')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_orders'));
    }

    public function products()
    {
        $products = Product::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:food,drink,dessert,side,other',
            'is_available' => 'boolean',
            'stock' => 'integer|min:0',
            'image_url' => 'nullable|url',
        ]);

        Product::create($request->all());

        return redirect()->route('admin.products')
            ->with('success', '商品を追加しました');
    }

    public function editProduct($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:food,drink,dessert,side,other',
            'is_available' => 'boolean',
            'stock' => 'integer|min:0',
            'image_url' => 'nullable|url',
        ]);

        $product = Product::findOrFail($id);
        $product->update($request->all());

        return redirect()->route('admin.products')
            ->with('success', '商品を更新しました');
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products')
            ->with('success', '商品を削除しました');
    }

    public function orders(Request $request)
    {
        $query = Order::with('user', 'items.product');
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        $orders = $query->latest()->paginate(20)->appends($request->query());
        
        $statusCounts = [
            'all' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'confirmed' => Order::where('status', 'confirmed')->count(),
            'preparing' => Order::where('status', 'preparing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
        
        return view('admin.orders', compact('orders', 'statusCounts'));
    }

    public function orderDetail($id)
    {
        $order = Order::with('user', 'items.product')->findOrFail($id);
        return view('admin.order-detail', compact('order'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        $statusLabels = [
            'pending' => '未処理',
            'confirmed' => '確認済み',
            'preparing' => '準備中',
            'ready' => '準備完了',
            'completed' => '完了',
            'cancelled' => 'キャンセル',
        ];

        return redirect()->back()
            ->with('success', "注文ステータスを「{$statusLabels[$request->status]}」に更新しました");
    }

    public function completeOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'completed']);

        return redirect()->back()
            ->with('success', '注文を完了しました');
    }

    public function members()
    {
        $members = Member::with('user')
            ->latest()
            ->paginate(20);
        return view('admin.members', compact('members'));
    }

    public function memberDetail($id)
    {
        $member = Member::with('user', 'pointTransactions.order')
            ->findOrFail($id);
        return view('admin.member-detail', compact('member'));
    }

    public function settings()
    {
        $businessHours = ShopSetting::getBusinessHours();
        $timeSlots = ShopSetting::getReservationTimeSlots();
        $closedDays = ShopSetting::getClosedDays();
        $closedDates = ShopSetting::getClosedDates();
        $advanceDays = ShopSetting::getAdvanceBookingDays();
        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();

        return view('admin.settings', compact('businessHours', 'timeSlots', 'closedDays', 'closedDates', 'advanceDays', 'reservationCapacity'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'business_hours_start' => 'required|date_format:H:i',
            'business_hours_end' => 'required|date_format:H:i|after:business_hours_start',
            'reservation_time_slots' => 'required|array|min:1',
            'reservation_time_slots.*' => 'required|date_format:H:i',
            'closed_days' => 'nullable|array',
            'closed_days.*' => 'integer|min:0|max:6',
            'closed_dates' => 'nullable|array',
            'closed_dates.*' => 'date_format:Y-m-d',
            'advance_booking_days' => 'required|integer|min:1|max:365',
            'reservation_capacity_per_slot' => 'required|integer|min:' . self::RESERVATION_CAPACITY_MIN,
        ]);

        ShopSetting::setValue('business_hours_start', $request->business_hours_start, 'time');
        ShopSetting::setValue('business_hours_end', $request->business_hours_end, 'time');
        ShopSetting::setValue('reservation_time_slots', $request->reservation_time_slots, 'json');
        ShopSetting::setValue('closed_days', $request->closed_days ?? [], 'json');
        $closedDates = array_values(array_filter($request->closed_dates ?? [], function ($date) {
            return $date !== null && $date !== '';
        }));
        ShopSetting::setValue('closed_dates', $closedDates, 'json');
        ShopSetting::setValue('advance_booking_days', $request->advance_booking_days, 'integer');
        ShopSetting::setValue('reservation_capacity_per_slot', $request->reservation_capacity_per_slot, 'integer');

        return redirect()->route('admin.settings')
            ->with('success', '設定を更新しました');
    }

    public function reservations(Request $request)
    {
        $query = Reservation::with('user');
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date')) {
            $query->whereDate('reserved_at', $request->date);
        }
        
        $reservations = $query->orderBy('reserved_at', 'desc')->paginate(20)->appends($request->query());
        
        $statusCounts = [
            'all' => Reservation::count(),
            'pending' => Reservation::where('status', ReservationStatus::PENDING)->count(),
            'confirmed' => Reservation::where('status', ReservationStatus::CONFIRMED)->count(),
            'completed' => Reservation::where('status', ReservationStatus::COMPLETED)->count(),
            'cancelled' => Reservation::where('status', ReservationStatus::CANCELLED)->count(),
        ];

        $selectedDate = $request->date;
        $dateStatusCounts = null;
        $timeSlotSummary = [];
        $selectedDateLabel = null;
        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();
        $timeSlots = ShopSetting::getReservationTimeSlots();

        if ($selectedDate !== null && $selectedDate !== '') {
            $dateInstance = Carbon::parse($selectedDate);
            $selectedDateLabel = $dateInstance->format('Y年m月d日');

            $dateStatusCounts = [
                'all' => Reservation::whereDate('reserved_at', $selectedDate)->count(),
                'pending' => Reservation::whereDate('reserved_at', $selectedDate)
                    ->where('status', ReservationStatus::PENDING)
                    ->count(),
                'confirmed' => Reservation::whereDate('reserved_at', $selectedDate)
                    ->where('status', ReservationStatus::CONFIRMED)
                    ->count(),
                'completed' => Reservation::whereDate('reserved_at', $selectedDate)
                    ->where('status', ReservationStatus::COMPLETED)
                    ->count(),
                'cancelled' => Reservation::whereDate('reserved_at', $selectedDate)
                    ->where('status', ReservationStatus::CANCELLED)
                    ->count(),
            ];

            $activeReservations = Reservation::whereDate('reserved_at', $selectedDate)
                ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
                ->get(['reserved_at']);

            $slotCounts = [];
            foreach ($activeReservations as $reservation) {
                $reservedAt = $reservation->reserved_at instanceof Carbon
                    ? $reservation->reserved_at
                    : Carbon::parse($reservation->reserved_at);
                $timeKey = $reservedAt->format('H:i');
                if (!isset($slotCounts[$timeKey])) {
                    $slotCounts[$timeKey] = 0;
                }
                $slotCounts[$timeKey] += 1;
            }

            foreach ($timeSlots as $slot) {
                $slotCount = $slotCounts[$slot] ?? 0;
                $timeSlotSummary[] = [
                    'time' => $slot,
                    'count' => $slotCount,
                    'capacity' => $reservationCapacity,
                    'is_full' => $slotCount >= $reservationCapacity,
                ];
            }
        }
        
        return view('admin.reservations', compact('reservations', 'statusCounts', 'selectedDate', 'selectedDateLabel', 'dateStatusCounts', 'timeSlotSummary', 'reservationCapacity'));
    }

    public function reservationDetail($id)
    {
        $reservation = Reservation::with('user')->findOrFail($id);
        return view('admin.reservation-detail', compact('reservation'));
    }

    public function updateReservationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                ReservationStatus::PENDING,
                ReservationStatus::CONFIRMED,
                ReservationStatus::COMPLETED,
                ReservationStatus::CANCELLED,
            ]),
        ]);

        $reservation = Reservation::findOrFail($id);
        $reservation->update(['status' => $request->status]);

        $statusLabels = [
            ReservationStatus::PENDING => '予約待ち',
            ReservationStatus::CONFIRMED => '確認済み',
            ReservationStatus::COMPLETED => '完了',
            ReservationStatus::CANCELLED => 'キャンセル',
        ];

        return redirect()->back()
            ->with('success', "予約ステータスを「{$statusLabels[$request->status]}」に更新しました");
    }

    public function completeReservation($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update(['status' => ReservationStatus::COMPLETED]);

        return redirect()->back()
            ->with('success', '予約を完了しました');
    }
}
