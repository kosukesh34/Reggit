<?php

namespace App\UseCases\Admin\Chat;

use App\Enums\ReservationStatus;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\Reservation;
use App\Models\StampCard;

class AdminChatMemberContextUseCase
{
    /**
     * 会話の横に出す会員の予約と、配布できるクーポン・スタンプカード。権限のない項目は空で返す。
     *
     * @return array{reservations: list<array<string, mixed>>, coupons: list<array<string, mixed>>, stamp_cards: list<array<string, mixed>>, can_issue_coupons: bool}
     */
    public function handle(int $memberId, AdminUser $admin): array
    {
        $target = Member::query()->findOrFail($memberId);
        $reservations = $admin->hasPermission('reservations.view') && $target->user_id !== null
            ? Reservation::query()
                ->where('user_id', $target->user_id)
                ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
                ->where('reserved_at', '>=', now()->startOfDay())
                ->orderBy('reserved_at')
                ->limit(10)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'id' => $reservation->id,
                    'number' => (string) $reservation->reservation_number,
                    'reserved_at' => $reservation->reserved_at?->toIso8601String(),
                    'people' => (int) $reservation->number_of_people,
                    'course' => (string) ($reservation->course_name ?? ''),
                    'status' => ReservationStatus::getLabel((string) $reservation->status),
                ])->all()
            : [];
        $coupons = $admin->hasPermission('members.manage')
            ? Coupon::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>', now()))
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'end_at'])
                ->map(fn (Coupon $coupon): array => ['id' => $coupon->id, 'name' => $coupon->name, 'end_at' => $coupon->end_at?->toIso8601String()])
                ->all()
            : [];

        $canManage = $admin->hasPermission('members.manage');
        $current = MemberStamp::query()->where('member_id', $target->id)->pluck('current_stamps', 'stamp_card_id');
        $stampCards = $canManage
            ? StampCard::query()->where('is_active', true)->orderBy('id')->limit(50)->get(['id', 'name', 'required_stamps'])
                ->map(fn (StampCard $card): array => [
                    'id' => $card->id,
                    'name' => $card->name,
                    'required' => (int) $card->required_stamps,
                    'current' => (int) ($current[$card->id] ?? 0),
                ])->all()
            : [];

        return ['reservations' => $reservations, 'coupons' => $coupons, 'stamp_cards' => $stampCards, 'can_issue_coupons' => $canManage];
    }
}
