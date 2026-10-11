<?php

namespace App\Services;

use App\Models\MemberLotteryEntitlement;
use App\Models\MemberStamp;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($user): array {
            $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
            $member = $user->member()->where('project_id', $projectId)->lockForUpdate()->first();
            if ($member === null) {
                throw new RuntimeException('会員登録が必要です', 401);
            }

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
                'points' => $member->getDisplayPoints(),
                'stamps_total' => $stampsTotal,
                'pending_lottery_count' => $pendingLotteryCount,
                'updated_at' => now()->toIso8601String(),
            ];
        }, 3);
    }
}
