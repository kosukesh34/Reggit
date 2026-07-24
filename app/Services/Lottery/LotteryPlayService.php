<?php

namespace App\Services\Lottery;

use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\Settings\ProjectLotterySetting;
use App\Services\CouponIssueService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LotteryPlayService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
    ) {}

    /**
     * @return array{
     *     gacha_enabled: bool,
     *     scratch_enabled: bool,
     *     entitlements: list<array{id: int, reward_type: string, expires_at: string|null}>,
     *     prize_labels: array{gacha: list<string>, scratch: list<string>}
     * }
     */
    public function getMemberLotteryState(Member $member): array
    {
        $projectId = (int) $member->project_id;
        $settings = ProjectLotterySetting::query()->firstOrCreate(
            ['project_id' => $projectId],
            ProjectLotterySetting::getDefaultAttributes()
        );

        $entitlements = MemberLotteryEntitlement::query()
            ->where('project_id', $projectId)
            ->where('member_id', (int) $member->id)
            ->where('is_done', false)
            ->orderBy('id')
            ->get()
            ->filter(fn (MemberLotteryEntitlement $row) => $row->isPlayable())
            ->values();

        return [
            'gacha_enabled' => $settings->gacha_enabled === true,
            'scratch_enabled' => $settings->scratch_enabled === true,
            'entitlements' => $entitlements->map(fn (MemberLotteryEntitlement $row) => [
                'id' => (int) $row->id,
                'reward_type' => (string) $row->reward_type,
                'expires_at' => $row->expires_at?->toIso8601String(),
            ])->all(),
            'prize_labels' => [
                'gacha' => $this->prizeLabels($projectId, 'gacha'),
                'scratch' => $this->prizeLabels($projectId, 'scratch'),
            ],
        ];
    }

    /**
     * @return array{
     *     entitlement_id: int,
     *     reward_type: string,
     *     coupon: array{id: int, name: string, description: string|null, image_url: string|null},
     *     member_coupon_id: int
     * }
     */
    public function play(Member $member, int $entitlementId): array
    {
        return DB::transaction(function () use ($member, $entitlementId) {
            /** @var MemberLotteryEntitlement|null $entitlement */
            $entitlement = MemberLotteryEntitlement::query()
                ->where('project_id', (int) $member->project_id)
                ->where('member_id', (int) $member->id)
                ->where('id', $entitlementId)
                ->lockForUpdate()
                ->first();

            if ($entitlement === null) {
                throw new RuntimeException('プレイ可能な権利がありません', 404);
            }
            if ($entitlement->is_done === true) {
                throw new RuntimeException('すでにプレイ済みです', 409);
            }
            if ($entitlement->expires_at !== null && $entitlement->expires_at->isPast()) {
                throw new RuntimeException('権利の有効期限が切れています', 410);
            }

            $prize = $this->drawPrize((int) $member->project_id, (string) $entitlement->reward_type);
            $coupon = Coupon::query()
                ->where('project_id', (int) $member->project_id)
                ->where('id', (int) $prize->coupon_id)
                ->where('is_active', true)
                ->first();
            if ($coupon === null) {
                throw new RuntimeException('景品クーポンが見つかりません', 422);
            }

            $this->couponIssueService->issueManualCoupon($member, $coupon);
            $memberCoupon = $member->memberCoupons()
                ->where('coupon_id', (int) $coupon->id)
                ->orderByDesc('id')
                ->first();
            if ($memberCoupon === null) {
                throw new RuntimeException('クーポンの発行に失敗しました', 500);
            }

            $entitlement->is_done = true;
            $entitlement->done_at = now();
            $entitlement->won_coupon_id = (int) $coupon->id;
            $entitlement->member_coupon_id = (int) $memberCoupon->id;
            $entitlement->save();

            return [
                'entitlement_id' => (int) $entitlement->id,
                'reward_type' => (string) $entitlement->reward_type,
                'coupon' => [
                    'id' => (int) $coupon->id,
                    'name' => (string) $coupon->name,
                    'description' => $coupon->description !== null ? (string) $coupon->description : null,
                    'image_url' => $coupon->image_url !== null ? (string) $coupon->image_url : null,
                ],
                'member_coupon_id' => (int) $memberCoupon->id,
            ];
        });
    }

    private function drawPrize(int $projectId, string $rewardType): LotteryPrize
    {
        /** @var Collection<int, LotteryPrize> $prizes */
        $prizes = LotteryPrize::query()
            ->where('project_id', $projectId)
            ->where('reward_type', $rewardType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($prizes->isEmpty()) {
            throw new RuntimeException('景品が設定されていません', 422);
        }

        $total = (int) $prizes->sum('probability');
        if ($total <= 0) {
            throw new RuntimeException('景品確率が不正です', 422);
        }

        $roll = random_int(1, $total);
        $cursor = 0;
        foreach ($prizes as $prize) {
            $cursor += (int) $prize->probability;
            if ($roll <= $cursor) {
                return $prize;
            }
        }

        return $prizes->last();
    }

    /** @return list<string> */
    private function prizeLabels(int $projectId, string $rewardType): array
    {
        return LotteryPrize::query()
            ->with('coupon:id,name')
            ->where('project_id', $projectId)
            ->where('reward_type', $rewardType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LotteryPrize $prize) => (string) ($prize->coupon->name ?? ''))
            ->filter(fn (string $name) => $name !== '')
            ->values()
            ->all();
    }
}
