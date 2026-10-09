<?php

namespace App\Services\Lottery;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\Scopes\ProjectScope;
use App\Models\Settings\ProjectLotterySetting;
use App\Util\LogHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LotteryEntitlementService
{
    public function grantForPurchase(
        Member $member,
        float $amount,
        string $sourceType,
        string $sourceId,
    ): void {
        DB::transaction(function () use ($member, $amount, $sourceType, $sourceId): void {
            $projectId = (int) $member->project_id;
            $member = $member->newQuery()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $projectId)->whereKey($member->id)
                ->lockForUpdate()->firstOrFail();
            $settings = ProjectLotterySetting::query()->firstOrCreate(
                ['project_id' => $projectId],
                ProjectLotterySetting::getDefaultAttributes()
            );

            foreach (LotteryRewardType::keys() as $rewardType) {
                if (! $this->isRewardEnabled($settings, $rewardType)) {
                    continue;
                }
                if (! $this->passesGrantTrigger($settings, $member, $rewardType, $amount)) {
                    continue;
                }
                $this->createIfMissing($member, $rewardType, $sourceType, $sourceId, $settings);
            }
        }, 3);
    }

    public function grantForStamp(Member $member, int $stampCardId, int $stampCount, int $cycleIndex = 0): void
    {
        $settings = ProjectLotterySetting::query()->firstOrCreate(
            ['project_id' => (int) $member->project_id],
            ProjectLotterySetting::getDefaultAttributes()
        );
        $this->createIfMissing(
            $member,
            LotteryRewardType::GACHA,
            LotterySourceType::STAMP,
            $stampCardId.':'.(int) $member->id.':'.$cycleIndex.':'.$stampCount,
            $settings,
        );
    }

    /**
     * 取引キャンセル／返品時の取り消し。
     *
     * - 未消化の権利: 削除
     * - 当選済みかつ未使用のクーポン: 回収（削除）
     * - 当選済みかつ使用済みのクーポン: 回収しない
     * - ハズレ済み: 追加処理なし（権利レコードは履歴として残す）
     */
    public function revokePendingForSource(
        Member $member,
        string $sourceType,
        string $sourceId,
    ): void {
        DB::transaction(function () use ($member, $sourceType, $sourceId): void {
            $projectId = (int) $member->project_id;
            $memberId = (int) $member->id;

            MemberLotteryEntitlement::query()
                ->where('project_id', $projectId)
                ->where('member_id', $memberId)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('is_done', false)
                ->delete();

            $played = MemberLotteryEntitlement::query()
                ->where('project_id', $projectId)
                ->where('member_id', $memberId)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('is_done', true)
                ->whereNotNull('member_coupon_id')
                ->lockForUpdate()
                ->get();

            foreach ($played as $entitlement) {
                $memberCoupon = MemberCoupon::query()
                    ->where('project_id', $projectId)
                    ->where('member_id', $memberId)
                    ->where('id', (int) $entitlement->member_coupon_id)
                    ->lockForUpdate()
                    ->first();
                if ($memberCoupon === null) {
                    $entitlement->member_coupon_id = null;
                    $entitlement->save();

                    continue;
                }
                if ($memberCoupon->isUsed()) {
                    continue;
                }

                CouponRedemption::query()
                    ->where('member_coupon_id', (int) $memberCoupon->id)
                    ->delete();
                $memberCoupon->delete();

                $entitlement->member_coupon_id = null;
                $entitlement->save();

                LogHelper::info('[Lottery] revoked unused won coupon on cancel', [
                    'project_id' => $projectId,
                    'member_id' => $memberId,
                    'entitlement_id' => (int) $entitlement->id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);
            }
        });
    }

    private function isRewardEnabled(ProjectLotterySetting $settings, string $rewardType): bool
    {
        if ($rewardType === LotteryRewardType::GACHA) {
            return $settings->gacha_enabled === true;
        }
        if ($rewardType === LotteryRewardType::SCRATCH) {
            return $settings->scratch_enabled === true;
        }
        if ($rewardType === LotteryRewardType::ROULETTE) {
            return $settings->roulette_enabled === true;
        }

        return false;
    }

    private function passesGrantTrigger(
        ProjectLotterySetting $settings,
        Member $member,
        string $rewardType,
        float $amount,
    ): bool {
        $trigger = (string) $settings->grant_trigger;
        if ($trigger === LotteryGrantTrigger::MIN_AMOUNT) {
            $min = $settings->min_amount !== null ? (float) $settings->min_amount : 0.0;

            return $amount >= $min;
        }
        if ($trigger === LotteryGrantTrigger::ONCE_PER_DAY) {
            $start = Carbon::today();
            $existing = MemberLotteryEntitlement::query()
                ->where('project_id', (int) $member->project_id)
                ->where('member_id', (int) $member->id)
                ->where('reward_type', $rewardType)
                ->where('created_at', '>=', $start)
                ->lockForUpdate()->first(['id']);

            return $existing === null;
        }

        return true;
    }

    private function createIfMissing(
        Member $member,
        string $rewardType,
        string $sourceType,
        string $sourceId,
        ProjectLotterySetting $settings,
    ): void {
        $existing = MemberLotteryEntitlement::query()
            ->where('project_id', (int) $member->project_id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('reward_type', $rewardType)
            ->lockForUpdate()->first(['id']);
        if ($existing !== null) {
            return;
        }

        $expiresAt = null;
        if ($settings->entitlement_expires_hours !== null && (int) $settings->entitlement_expires_hours > 0) {
            $expiresAt = now()->addHours((int) $settings->entitlement_expires_hours);
        }

        MemberLotteryEntitlement::query()->create([
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'reward_type' => $rewardType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'is_done' => false,
            'expires_at' => $expiresAt,
        ]);
    }
}
