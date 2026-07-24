<?php

namespace App\Services\Lottery;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\Settings\ProjectLotterySetting;
use Illuminate\Support\Carbon;

class LotteryEntitlementService
{
    public function grantForPurchase(
        Member $member,
        float $amount,
        string $sourceType,
        string $sourceId,
    ): void {
        $projectId = (int) $member->project_id;
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
    }

    public function revokePendingForSource(
        Member $member,
        string $sourceType,
        string $sourceId,
    ): void {
        MemberLotteryEntitlement::query()
            ->where('project_id', (int) $member->project_id)
            ->where('member_id', (int) $member->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('is_done', false)
            ->delete();
    }

    private function isRewardEnabled(ProjectLotterySetting $settings, string $rewardType): bool
    {
        if ($rewardType === LotteryRewardType::GACHA) {
            return $settings->gacha_enabled === true;
        }
        if ($rewardType === LotteryRewardType::SCRATCH) {
            return $settings->scratch_enabled === true;
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
            $exists = MemberLotteryEntitlement::query()
                ->where('project_id', (int) $member->project_id)
                ->where('member_id', (int) $member->id)
                ->where('reward_type', $rewardType)
                ->where('created_at', '>=', $start)
                ->exists();

            return $exists === false;
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
        $exists = MemberLotteryEntitlement::query()
            ->where('project_id', (int) $member->project_id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('reward_type', $rewardType)
            ->exists();
        if ($exists) {
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
