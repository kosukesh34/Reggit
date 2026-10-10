<?php

namespace App\Services\Smaregi;

use App\Domain\Settings\Repositories\RankSettings;
use App\Models\Member;
use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\SmaregiMemberRankSync;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 会員ランクとランク別ポイント付与率をスマレジ顧客へ反映する。
 * ランクマスタ同期（SmaregiCustomerRankSyncService）とは責務を分ける。
 */
class SmaregiCustomerRankAssignmentService
{
    private const MEMBER_SYNC_CHUNK_SIZE = 50;

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private RankSettings $shopSettingRepository,
    ) {}

    public function assignRankToCustomer(Member $member, string $rankKey): void
    {
        DB::transaction(function () use ($member, $rankKey): void {
            $current = Member::withoutGlobalScopes()->whereKey($member->id)
                ->where('project_id', $member->project_id)->whereNull('deleted_at')
                ->lockForUpdate()->first();
            if ($current === null || trim((string) $current->smaregi_customer_id) === ''
                || $rankKey === '' || (string) $current->rank !== $rankKey) {
                return;
            }
            SmaregiMemberRankSync::upsert([[
                'member_id' => $current->id,
                'project_id' => $current->project_id,
                'request_token' => (string) Str::uuid(),
                'retry_at' => now(),
            ]], ['member_id'], ['project_id', 'request_token', 'retry_at']);
            DB::afterCommit(function () use ($current): void {
                try {
                    $this->retryPending((int) $current->id);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    public function retryPending(int $memberId): bool
    {
        $leaseToken = (string) Str::uuid();
        $request = DB::transaction(function () use ($memberId, $leaseToken): ?SmaregiMemberRankSync {
            $request = SmaregiMemberRankSync::whereKey($memberId)->where('retry_at', '<=', now())
                ->where(fn ($query) => $query->whereNull('leased_until')->orWhere('leased_until', '<=', now()))
                ->lockForUpdate()->first();
            if ($request === null) {
                return null;
            }
            $request->update(['leased_until' => now()->addMinutes(5), 'lease_token' => $leaseToken]);

            return $request;
        });
        if ($request === null) {
            return true;
        }
        $owned = fn () => SmaregiMemberRankSync::whereKey($memberId)
            ->where('request_token', $request->request_token)->where('lease_token', $leaseToken);
        $previousProject = AdminContext::getCurrentProject();
        try {
            AdminContext::setCurrentProject(Project::findOrFail($request->project_id));
            $member = Member::withoutGlobalScopes()->whereKey($memberId)
                ->where('project_id', $request->project_id)->whereNull('deleted_at')->first();
            if ($member === null || trim((string) $member->smaregi_customer_id) === '') {
                $owned()->delete();

                return true;
            }
            // Read current desired rank so an older request cannot restore an obsolete rank.
            $rankKey = (string) $member->rank;
            $body = $this->buildCustomerRankBody((int) $member->project_id, $rankKey);
            $contractId = trim((string) $this->smaregiApi->getContractIdForProject((int) $member->project_id));
            if ($body === [] || $contractId === '') {
                $owned()->update(['retry_at' => now()->addHour()]);

                return true;
            }
            $this->smaregiApi->clearLastErrorDetail();
            $result = $this->smaregiApi->updateCustomer($contractId, (string) $member->smaregi_customer_id, $body);
            if ($result === null) {
                LogHelper::warning('[Member] smaregi customer rank sync failed', [
                    'project_id' => (int) $member->project_id,
                    'member_id' => $memberId,
                    'rank_key' => $rankKey,
                    'detail' => $this->smaregiApi->getLastErrorDetail(),
                ]);
                $owned()->update(['retry_at' => now()->addMinute()]);

                return false;
            } else {
                $owned()->delete();
            }
        } catch (\Throwable $exception) {
            report($exception);
            $owned()->update(['retry_at' => now()->addMinute()]);

            return false;
        } finally {
            AdminContext::setCurrentProject($previousProject);
            SmaregiMemberRankSync::whereKey($memberId)->where('lease_token', $leaseToken)
                ->update(['leased_until' => null, 'lease_token' => null]);
        }

        return true;
    }

    /**
     * プロジェクト内のスマレジ連携済み会員へ、現行ランクの付与率を一括反映する。
     */
    public function syncPointRatesForProject(int $projectId): void
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            return;
        }

        Member::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_customer_id')
            ->where('smaregi_customer_id', '!=', '')
            ->orderBy('id')
            ->chunkById(self::MEMBER_SYNC_CHUNK_SIZE, function ($members): void {
                foreach ($members as $member) {
                    $rankKey = trim((string) ($member->rank ?? ''));
                    if ($rankKey === '') {
                        continue;
                    }
                    $this->assignRankToCustomer($member, $rankKey);
                }
            });
    }

    /**
     * @return array{rank?: string, pointGivingUnitPrice?: string, pointGivingUnit?: string}
     */
    public function buildCustomerRankBody(int $projectId, string $rankKey): array
    {
        $smaregiRankId = $this->resolveSmaregiRankId($projectId, $rankKey);
        if ($smaregiRankId === null) {
            return [];
        }

        $body = ['rank' => $smaregiRankId];
        $unitPrice = $this->resolvePointGivingUnitPrice($projectId, $rankKey);
        $body['pointGivingUnitPrice'] = (string) $unitPrice;
        $body['pointGivingUnit'] = SmaregiCustomerRankSyncService::POINT_GIVING_UNIT;

        return $body;
    }

    public function resolveSmaregiRankId(int $projectId, string $rankKey): ?string
    {
        if ($rankKey === '') {
            return null;
        }
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') !== $rankKey) {
                continue;
            }
            $smaregiRankId = isset($rank['smaregi_rank_id']) ? trim((string) $rank['smaregi_rank_id']) : '';

            return $smaregiRankId !== '' ? $smaregiRankId : null;
        }

        return null;
    }

    public function resolvePointGivingUnitPrice(int $projectId, string $rankKey): int
    {
        $rate = $this->shopSettingRepository->getPointEarnRateForRank($projectId, $rankKey);

        return max(1, min(SmaregiCustomerRankSyncService::POINT_GIVING_UNIT_PRICE_MAX, $rate));
    }
}
