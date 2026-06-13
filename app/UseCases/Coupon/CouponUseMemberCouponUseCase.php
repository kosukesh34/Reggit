<?php

namespace App\UseCases\Coupon;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\MemberCoupon;
use App\Services\UserService;
use App\UseCases\Coupon\Data\CouponUseMemberCouponOutput;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponUseMemberCouponUseCase
{
    public function __construct(private UserService $userService) {}

    public function handle(Request $request, int $memberCouponId): CouponUseMemberCouponOutput
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            throw ValidationException::withMessages([
                'member' => ['会員登録が必要です'],
            ]);
        }

        return DB::transaction(function () use ($projectId, $member, $memberCouponId) {
            $memberCoupon = MemberCoupon::query()
                ->where('project_id', $projectId)
                ->where('member_id', $member->id)
                ->where('id', $memberCouponId)
                ->lockForUpdate()
                ->with('coupon')
                ->first();

            if ($memberCoupon === null) {
                throw ValidationException::withMessages([
                    'member_coupon_id' => ['クーポンが見つかりません'],
                ]);
            }

            if ($memberCoupon->status === MemberCoupon::STATUS_USED) {
                throw ValidationException::withMessages([
                    'member_coupon_id' => ['このクーポンは使用済みです'],
                ]);
            }

            $validFrom = $memberCoupon->valid_from;
            if ($validFrom !== null) {
                $start = $validFrom instanceof \DateTimeInterface
                    ? $validFrom
                    : new \DateTimeImmutable((string) $validFrom);
                if ($start->getTimestamp() > time()) {
                    throw ValidationException::withMessages([
                        'member_coupon_id' => ['このクーポンはまだ利用開始前です'],
                    ]);
                }
            }

            $validUntil = $memberCoupon->valid_until;
            if ($validUntil !== null) {
                $end = $validUntil instanceof \DateTimeInterface
                    ? $validUntil
                    : new \DateTimeImmutable((string) $validUntil);
                if ($end->getTimestamp() < time()) {
                    throw ValidationException::withMessages([
                        'member_coupon_id' => ['このクーポンは期限切れです'],
                    ]);
                }
            }

            $usedAt = now();
            $memberCoupon->update([
                'status' => MemberCoupon::STATUS_USED,
                'used_at' => $usedAt,
            ]);

            CouponRedemption::create([
                'project_id' => $projectId,
                'coupon_id' => $memberCoupon->coupon_id,
                'member_id' => $member->id,
                'member_coupon_id' => $memberCoupon->id,
                'order_id' => null,
                'used_at' => $usedAt,
            ]);

            $coupon = $memberCoupon->coupon;
            if ($coupon !== null) {
                $coupon->increment('used_count');
            }

            return $this->buildOutput($memberCoupon, $usedAt);
        });
    }

    private function buildOutput(MemberCoupon $memberCoupon, \DateTimeInterface $usedAt): CouponUseMemberCouponOutput
    {
        $couponName = $memberCoupon->coupon_name_snapshot;
        if ($couponName === null || $couponName === '') {
            $couponName = $memberCoupon->coupon?->name ?? '';
        }

        $code = $memberCoupon->coupon?->code;
        $useDisplay = (string) ($memberCoupon->coupon?->use_display ?? Coupon::USE_DISPLAY_BARCODE);
        if ($useDisplay === Coupon::USE_DISPLAY_NONE && $code !== null && $code !== '') {
            $useDisplay = Coupon::USE_DISPLAY_BARCODE;
        }

        return new CouponUseMemberCouponOutput(
            (int) $memberCoupon->id,
            $couponName,
            'used',
            $usedAt->format(DATE_ATOM),
            $useDisplay,
            $code
        );
    }
}
