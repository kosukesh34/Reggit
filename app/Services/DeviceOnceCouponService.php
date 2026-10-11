<?php

namespace App\Services;

use App\Domain\Coupon\Policies\CouponIssuePolicy;
use App\Models\Coupon;
use App\Models\CouponDeviceIssue;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeviceOnceCouponService
{
    public function __construct(private CouponIssuePolicy $couponIssuePolicy) {}

    /** @return list<array<string, mixed>> */
    public function available(User $user, string $deviceIdentifier): array
    {
        $member = $user->member;
        if ($member === null || $deviceIdentifier === '') {
            return [];
        }

        $claimedIds = CouponDeviceIssue::query()
            ->where('project_id', (int) $member->project_id)
            ->where('device_identifier', $deviceIdentifier)
            ->pluck('coupon_id');

        return Coupon::query()
            ->where('project_id', (int) $member->project_id)
            ->where('is_active', true)
            ->where('device_once_enabled', true)
            ->whereNotIn('id', $claimedIds)
            ->where(fn ($query) => $query->whereNull('start_at')->orWhere('start_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>=', now()))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Coupon $coupon): array => [
                'id' => (int) $coupon->id,
                'name' => (string) $coupon->name,
                'description' => (string) ($coupon->description ?? ''),
                'image_url' => $coupon->image_url,
                'valid_from' => $coupon->start_at?->toIso8601String(),
                'valid_until' => $coupon->end_at?->toIso8601String(),
            ])
            ->all();
    }

    public function claim(User $user, Coupon $coupon, string $deviceIdentifier): MemberCoupon
    {
        $member = $user->member;
        if ($member === null) {
            throw ValidationException::withMessages(['member' => '会員登録が必要です']);
        }
        if ($deviceIdentifier === '') {
            throw ValidationException::withMessages(['device_identifier' => '端末情報を確認できません']);
        }

        try {
            return DB::transaction(function () use ($user, $member, $coupon, $deviceIdentifier): MemberCoupon {
                $projectId = (int) $user->project_id;
                $owner = User::query()->where('project_id', $projectId)->whereKey($user->id)->lockForUpdate()->first();
                if ($owner === null || (int) $owner->authentication_version !== (int) $user->authentication_version) {
                    throw ValidationException::withMessages(['member' => 'ログインし直してください']);
                }
                $member = Member::query()->whereKey($member->id)
                    ->where('project_id', (int) $member->project_id)
                    ->where('user_id', (int) $user->id)->lockForUpdate()->first();
                if ($member === null) {
                    throw ValidationException::withMessages(['member' => '会員登録が必要です']);
                }

                $device = UserDevice::query()->where('project_id', $projectId)->where('user_id', $owner->id)
                    ->where('device_identifier', $deviceIdentifier)->lockForUpdate()->first();
                if ($device === null) {
                    throw ValidationException::withMessages(['device_identifier' => 'この端末を先に登録してください']);
                }
                $coupon = Coupon::query()->where('project_id', $projectId)->whereKey($coupon->id)->lockForUpdate()->first();
                if ($coupon === null || ! $coupon->is_active || ! $coupon->device_once_enabled) {
                    throw ValidationException::withMessages(['coupon' => 'このクーポンは獲得できません']);
                }
                if (! $this->couponIssuePolicy->isWithinIssuePeriod($coupon->start_at, $coupon->end_at, now())) {
                    throw ValidationException::withMessages(['coupon' => 'このクーポンは発行期間外です']);
                }

                $now = now();
                $deviceIssue = CouponDeviceIssue::create([
                    'project_id' => (int) $member->project_id,
                    'coupon_id' => (int) $coupon->id,
                    'member_id' => (int) $member->id,
                    'device_identifier' => $deviceIdentifier,
                    'issued_at' => $now,
                ]);

                [$validFrom, $validUntil] = $this->couponIssuePolicy->resolveValidityWindow(
                    $coupon->start_at,
                    $coupon->end_at,
                    $now,
                );
                $memberCoupon = MemberCoupon::create([
                    'project_id' => (int) $member->project_id,
                    'member_id' => (int) $member->id,
                    'coupon_id' => (int) $coupon->id,
                    'coupon_name_snapshot' => (string) $coupon->name,
                    'status' => MemberCoupon::STATUS_UNUSED,
                    'issued_at' => $now,
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'issue_source_type' => 'device_once',
                    'issue_source_id' => hash('sha256', $deviceIdentifier),
                    'issue_grant_key' => hash('sha256', $deviceIdentifier.'|'.$coupon->id),
                ]);
                $deviceIssue->update(['member_coupon_id' => (int) $memberCoupon->id]);

                return $memberCoupon;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['coupon' => 'この端末ではすでに獲得済みです']);
        }
    }

    public function claimById(User $user, int $couponId, string $deviceIdentifier): MemberCoupon
    {
        $coupon = Coupon::query()->findOrFail($couponId);

        return $this->claim($user, $coupon, $deviceIdentifier);
    }
}
