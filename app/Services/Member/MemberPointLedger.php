<?php

namespace App\Services\Member;

use App\Domain\Member\Services\PointLedgerBalanceCalculator;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\PointTransactionType;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Scopes\ProjectScope;
use App\Models\Scopes\StoreAccessScope;
use Illuminate\Support\Facades\DB;

/** 会員のポイント台帳（point_transactions）から残高・累計付与を求め、members の集計列を台帳に合わせる。 */
class MemberPointLedger
{
    public const POINTS_LEDGER_BACKFILL_DESCRIPTION = 'ポイント残高の整合';

    public function __construct(
        private PointLedgerBalanceCalculator $calculator,
        private OrderSettings $orderSettings,
    ) {}

    /** @return array{balance: int, expires_at: ?\DateTimeImmutable, points_expiring: int, has_transactions: bool} */
    public function balance(Member $member, bool $forUpdate = false): array
    {
        $query = $member->pointTransactions()->withoutGlobalScope(StoreAccessScope::class)
            ->withoutGlobalScope(ProjectScope::class)
            ->orderBy('created_at')->orderBy('id');
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        $transactions = $query
            ->get(['type', 'points', 'expires_at', 'order_id', 'created_at'])
            ->map(fn (PointTransaction $transaction): array => [
                'type' => $transaction->type,
                'points' => $transaction->points,
                'expires_at' => $transaction->expires_at,
                'order_id' => $transaction->order_id,
                'created_at' => $transaction->created_at,
            ])->all();

        return $this->calculator->calculate($transactions, now());
    }

    public function total(Member $member, bool $forUpdate = false): int
    {
        return $this->balance($member, $forUpdate)['balance'];
    }

    /** 表示・判定用。既存データに台帳がない場合はカラム値を使い、読み取り時には更新しない。 */
    public function displayPoints(Member $member): int
    {
        $balance = $this->balance($member);

        return $balance['has_transactions'] ? $balance['balance'] : max(0, (int) $member->points);
    }

    /** Backfill only members with no ledger, never resurrect spent or expired points. */
    public function sync(Member $member): void
    {
        DB::transaction(function () use ($member): void {
            $locked = $member->newQuery()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $member->project_id)->whereKey($member->id)
                ->lockForUpdate()->firstOrFail();
            // Current reads also see a competing backfill committed after an outer transaction's snapshot.
            $balance = $this->balance($locked, true);
            if (! $balance['has_transactions'] && (int) $locked->points > 0) {
                $this->backfillEarnedPoints($locked, (int) $locked->points);
            } elseif ((int) $locked->points !== $balance['balance']) {
                $locked->update(['points' => $balance['balance']]);
            }
            $member->points = $locked->points;
            $member->syncOriginalAttribute('points');
        }, 3);
    }

    public function recalculate(Member $member): void
    {
        $member->update(['points' => $this->total($member, true)]);
    }

    /** 台帳から累計付与ポイントを再計算して保存する */
    public function recalculateLifetimeGranted(Member $member): void
    {
        DB::transaction(function () use ($member): void {
            $locked = $member->newQuery()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $member->project_id)->whereKey($member->id)
                ->lockForUpdate()->first();
            if ($locked === null) {
                return;
            }
            $total = (int) $locked->pointTransactions()->withoutGlobalScope(StoreAccessScope::class)
                ->withoutGlobalScope(ProjectScope::class)
                ->lockForUpdate()->get(['type', 'points'])
                ->sum(fn (PointTransaction $tx) => self::lifetimeGrantedDelta((string) $tx->type, (int) $tx->points));
            $locked->update(['lifetime_granted_points' => max(0, $total)]);
            $member->lifetime_granted_points = $locked->lifetime_granted_points;
            $member->syncOriginalAttribute('lifetime_granted_points');
        }, 3);
    }

    /**
     * 累計付与ポイントの増減量（獲得・正の調整は加算、取消は減算）。
     */
    public static function lifetimeGrantedDelta(string $type, int $points): int
    {
        if ($type === PointTransactionType::EARNED || $type === PointTransactionType::ADJUSTED) {
            return max(0, $points);
        }

        if ($type === PointTransactionType::REVERSED) {
            return -abs($points);
        }

        return 0;
    }

    private function backfillEarnedPoints(Member $member, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        PointTransaction::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => $points,
            'description' => self::POINTS_LEDGER_BACKFILL_DESCRIPTION,
            'order_id' => null,
            'expires_at' => now()->addDays($this->orderSettings->getPointsValidDays((int) $member->project_id)),
        ]);
        $member->update(['points' => $this->total($member, true)]);
    }
}
