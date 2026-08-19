<?php

namespace App\Services;

use App\Models\MemberLotteryEntitlement;
use App\Models\MemberStamp;
use App\Models\User;
use RuntimeException;

class MemberWalletSummaryService
{
    /**
     * @return array{
     *     points: int,
     *     stamps_total: int,
     *     pending_lottery_count: int,
     *     updated_at: string
     * }
     */
    public function summarize(User $user): array
    {
        $member = $user->member;
        if ($member === null) {
            throw new RuntimeException('会員登録が必要です', 401);
        }

        $projectId = (int) $member->project_id;
        $stampsTotal = (int) MemberStamp::query()
            ->where('project_id', $projectId)
            ->where('member_id', (int) $member->id)
            ->sum('current_stamps');

        $pendingLotteryCount = MemberLotteryEntitlement::query()
            ->where('project_id', $projectId)
            ->where('member_id', (int) $member->id)
            ->where('is_done', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        return [
            'points' => (int) $member->points,
            'stamps_total' => $stampsTotal,
            'pending_lottery_count' => $pendingLotteryCount,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
