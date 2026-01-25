<?php

namespace App\Http\Controllers;

use App\Models\ShopSetting;
use App\Services\ReservationService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    private ReservationService $reservationService;
    private UserService $userService;

    public function __construct(
        ReservationService $reservationService,
        UserService $userService
    ) {
        $this->reservationService = $reservationService;
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            
            if ($user->member === null) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }
            
            $reservations = $this->reservationService->getUserReservations($user);

            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Failed to get reservations: ' . $e->getMessage());
            return response()->json(['error' => '予約の取得に失敗しました'], 500);
        }
    }

    public function getAvailableDates(Request $request)
    {
        try {
            $timeSlots = ShopSetting::getReservationTimeSlots();
            $advanceDays = ShopSetting::getAdvanceBookingDays();
            $closedDays = ShopSetting::getClosedDays();
            
            $availableDates = [];
            $today = now();
            
            for ($i = 1; $i <= $advanceDays; $i++) {
                $date = $today->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;
                
                if (!in_array($dayOfWeek, $closedDays, true)) {
                    $dateStr = $date->format('Y-m-d');
                    $availableDates[] = [
                        'date' => $dateStr,
                        'day_of_week' => $date->format('w'),
                        'display' => $date->format('Y年m月d日') . '(' . $this->getDayOfWeekName($dayOfWeek) . ')',
                        'time_slots' => $timeSlots,
                    ];
                }
            }

            return response()->json(['available_dates' => $availableDates]);
        } catch (\Exception $e) {
            Log::error('Failed to get available dates: ' . $e->getMessage());
            return response()->json(['error' => '利用可能日の取得に失敗しました'], 500);
        }
    }

    private function getDayOfWeekName(int $dayOfWeek): string
    {
        $days = ['日', '月', '火', '水', '木', '金', '土'];
        return $days[$dayOfWeek] ?? '';
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'reserved_at' => 'required|date|after:now',
                'number_of_people' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'バリデーションエラー',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $user = $this->userService->getOrCreateUser($request);
            
            if ($user->member === null) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }
            
            $validated['reserved_at'] = date('Y-m-d H:i:s', strtotime($validated['reserved_at']));
            $reservation = $this->reservationService->createReservation($user, $validated);

            return response()->json([
                'message' => '予約が完了しました',
                'reservation' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Reservation creation failed: ' . $e->getMessage());
            return response()->json(['error' => '予約に失敗しました'], 400);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            
            if ($user->member === null) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }
            
            $reservation = $this->reservationService->cancelReservation($id, $user);

            return response()->json([
                'message' => '予約をキャンセルしました',
                'reservation' => $reservation,
            ]);
        } catch (\Exception $e) {
            Log::error('Reservation cancellation failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
