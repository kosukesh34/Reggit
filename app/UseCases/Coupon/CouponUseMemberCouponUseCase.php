<?php

namespace App\UseCases\Coupon;

use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\UseCases\Coupon\Data\CouponUseMemberCouponOutput;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CouponUseMemberCouponUseCase
{
    public function __construct(
        private MemberCouponRepository $memberCouponRepository,
        private CurrentMemberPrincipal $principals,
    ) {}

    public function handle(User $user, int $memberCouponId): CouponUseMemberCouponOutput
    {
        $member = $user->member;

        if ($member === null) {
            throw ValidationException::withMessages([
                'member' => ['会員登録が必要です'],
            ]);
        }

        $projectId = (int) ($member->project_id ?? $user->project_id ?? 0);
        if ($projectId <= 0) {
            throw ValidationException::withMessages([
                'member' => ['店舗情報を特定できません'],
            ]);
        }

        return DB::transaction(function () use ($user, $projectId, $member, $memberCouponId) {
            $member = $this->principals->lock($user, $projectId)?->member;
            if ($member === null) {
                throw ValidationException::withMessages(['member' => ['会員登録が必要です']]);
            }

            $aggregate = $this->memberCouponRepository->findByIdForMemberForUpdate(
                $memberCouponId,
                (int) $member->id,
                $projectId,
            );

            if ($aggregate === null) {
                throw ValidationException::withMessages([
                    'member_coupon_id' => ['クーポンが見つかりません'],
                ]);
            }

            try {
                $aggregate->markAsUsed(now());
            } catch (\Exception $e) {
                throw ValidationException::withMessages([
                    'member_coupon_id' => [$e->getMessage()],
                ]);
            }

            $this->memberCouponRepository->save($aggregate);

            $usedAt = $aggregate->usedAt();
            if ($usedAt === null) {
                throw new \RuntimeException('usedAt must be set after markAsUsed');
            }

            return new CouponUseMemberCouponOutput(
                $aggregate->id()->value(),
                $aggregate->displayName(),
                'used',
                $usedAt->format(DATE_ATOM),
                $aggregate->useDisplay(),
                $aggregate->couponCode(),
            );
        });
    }
}
