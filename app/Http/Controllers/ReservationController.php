<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ShopSetting;
use App\Services\ReservationService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    private const DATE_FORMAT = 'Y-m-d';
    private const TIME_FORMAT = 'H:i';
    private const ACTIVE_STATUSES = [
        ReservationStatus::PENDING,
        ReservationStatus::CONFIRMED,
    ];

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
            $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();
            $excludeReservationId = $request->query('exclude_reservation_id');
            if ($excludeReservationId !== null) {
                $excludeReservationId = filter_var($excludeReservationId, FILTER_VALIDATE_INT) ?: null;
            }

            $availableDates = [];
            $today = now();
            $startDate = $today->copy()->addDay()->startOfDay();
            $endDate = $today->copy()->addDays($advanceDays)->endOfDay();
            $slotCounts = $this->getSlotCounts($startDate, $endDate, $excludeReservationId);
            
            for ($i = 1; $i <= $advanceDays; $i++) {
                $date = $today->copy()->addDays($i);
                $dateStr = $date->format(self::DATE_FORMAT);
                if (!ShopSetting::isDateAvailable($dateStr)) {
                    continue;
                }

                $availableSlots = [];
                foreach ($timeSlots as $slot) {
                    $slotCount = $slotCounts[$dateStr][$slot] ?? 0;
                    if ($slotCount < $reservationCapacity) {
                        $availableSlots[] = $slot;
                    }
                }

                if (count($availableSlots) === 0) {
                    continue;
                }

                $availableDates[] = [
                    'date' => $dateStr,
                    'day_of_week' => $date->format('w'),
                    'display' => $date->format('Y年m月d日') . '(' . $this->getDayOfWeekName($date->dayOfWeek) . ')',
                    'time_slots' => $availableSlots,
                ];
            }

            return response()->json([
                'available_dates' => $availableDates,
                'start_date' => $startDate->format(self::DATE_FORMAT),
                'end_date' => $endDate->format(self::DATE_FORMAT),
            ]);
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

    private function getSlotCounts(Carbon $startDate, Carbon $endDate, ?int $excludeReservationId = null): array
    {
        $query = Reservation::whereBetween('reserved_at', [$startDate, $endDate])
            ->whereIn('status', self::ACTIVE_STATUSES);
        $reservations = $query->get(['id', 'reserved_at']);

        $slotCounts = [];
        foreach ($reservations as $reservation) {
            if ($excludeReservationId !== null && (int) $reservation->id === $excludeReservationId) {
                continue;
            }
            $reservedAt = $reservation->reserved_at instanceof Carbon
                ? $reservation->reserved_at
                : Carbon::parse($reservation->reserved_at);
            $dateKey = $reservedAt->format(self::DATE_FORMAT);
            $timeKey = $reservedAt->format(self::TIME_FORMAT);
            if (!isset($slotCounts[$dateKey])) {
                $slotCounts[$dateKey] = [];
            }
            if (!isset($slotCounts[$dateKey][$timeKey])) {
                $slotCounts[$dateKey][$timeKey] = 0;
            }
            $slotCounts[$dateKey][$timeKey] += 1;
        }

        return $slotCounts;
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

            $reservedAt = Carbon::parse($validated['reserved_at']);
            $availabilityError = $this->getReservationAvailabilityError($reservedAt);
            if ($availabilityError !== null) {
                return response()->json(['error' => $availabilityError], 400);
            }
            
            $validated['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
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

    private function getReservationAvailabilityError(Carbon $reservedAt, ?int $excludeReservationId = null): ?string
    {
        $advanceDays = ShopSetting::getAdvanceBookingDays();
        $startDate = now()->addDay()->startOfDay();
        $endDate = now()->addDays($advanceDays)->endOfDay();

        if ($reservedAt->lt($startDate) || $reservedAt->gt($endDate)) {
            return '予約可能な期間外です';
        }

        $dateStr = $reservedAt->format(self::DATE_FORMAT);
        if (!ShopSetting::isDateAvailable($dateStr)) {
            return '選択した日は予約できません';
        }

        $timeStr = $reservedAt->format(self::TIME_FORMAT);
        $timeSlots = ShopSetting::getReservationTimeSlots();
        if (!in_array($timeStr, $timeSlots, true)) {
            return '選択した時間は予約できません';
        }

        $reservationCapacity = ShopSetting::getReservationCapacityPerSlot();
        if (!$this->isSlotAvailable($reservedAt, $reservationCapacity, $excludeReservationId)) {
            return '選択した時間帯は満席です';
        }

        return null;
    }

    private function isSlotAvailable(Carbon $reservedAt, int $reservationCapacity, ?int $excludeReservationId = null): bool
    {
        $slotStart = $reservedAt->copy()->startOfMinute();
        $slotEnd = $reservedAt->copy()->endOfMinute();
        $query = Reservation::whereBetween('reserved_at', [$slotStart, $slotEnd])
            ->whereIn('status', self::ACTIVE_STATUSES);
        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }
        $reservedCount = $query->count();

        return $reservedCount < $reservationCapacity;
    }

    public function update(Request $request, int $id)
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

            $reservedAt = Carbon::parse($validated['reserved_at']);
            $availabilityError = $this->getReservationAvailabilityError($reservedAt, $id);
            if ($availabilityError !== null) {
                return response()->json(['error' => $availabilityError], 400);
            }

            $validated['reserved_at'] = $reservedAt->format('Y-m-d H:i:s');
            $reservation = $this->reservationService->updateReservation($id, $user, $validated);

            return response()->json([
                'message' => '予約を更新しました',
                'reservation' => $reservation,
            ]);
        } catch (\Exception $e) {
            Log::error('Reservation update failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
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
