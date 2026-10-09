<?php

namespace App\Models;

use App\Domain\Member\Services\PointLedgerBalanceCalculator;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Enums\PointTransactionType;
use App\Models\Concerns\BelongsToProject;
use App\Models\Scopes\ProjectScope;
use App\Models\Scopes\StoreAccessScope;
use App\Util\StoreContext;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use BelongsToProject, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (self $member) {
            if ($member->customer_store_id !== null && ($member->wasRecentlyCreated || $member->wasChanged('customer_store_id'))) {
                $member->stores()->syncWithoutDetaching([(int) $member->customer_store_id => ['project_id' => $member->project_id]]);
            }
        });
        static::deleting(function (self $member) {
            $admin = app(StoreContext::class)->admin;
            abort_unless(app(StoreContext::class)->projectStoreCount <= 1 || $admin === null || $admin->isSystemAdmin() || $admin->isProjectAdmin(), 403, '共通会員の退会は本部管理者が行ってください。');
        });
    }

    public function getMemoAttribute($value)
    {
        $context = app(StoreContext::class);
        if ($context->projectStoreCount > 1 && $context->admin !== null && ! $context->admin->isSystemAdmin() && ! $context->admin->isProjectAdmin()) {
            if ($context->store === null) {
                return null;
            }

            return DB::table('member_stores')->where('member_id', $this->id)->where('project_id', $this->project_id)->where('store_id', $context->store->id)->value('memo');
        }
        if ($context->projectStoreCount > 1 && $context->store !== null && $context->admin !== null) {
            return DB::table('member_stores')->where('member_id', $this->id)->where('project_id', $this->project_id)->where('store_id', $context->store->id)->value('memo');
        }

        return $value;
    }

    public function getLastVisitedAtAttribute($value)
    {
        $context = app(StoreContext::class);
        if ($context->projectStoreCount > 1 && $context->admin !== null && $context->visibleStoreIds !== null) {
            $value = MemberVisit::where('project_id', $this->project_id)->where('member_id', $this->id)->max('visited_at');
        }

        return $value === null ? null : $this->asDateTime($value);
    }

    protected static function newFactory()
    {
        return MemberFactory::new();
    }

    protected $fillable = [
        'project_id',
        'user_id',
        'member_number',
        'smaregi_customer_id',
        'points',
        'lifetime_granted_points',
        'status',
        'rank',
        'last_visited_at',
        'birthday',
        'address',
        'postal_code',
        'prefecture',
        'city',
        'street',
        'sex',
        'mail_receive_flag',
        'customer_store_id',
        'notification_preference',
        'profile_extra',
        'memo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'points' => 'integer',
        'lifetime_granted_points' => 'integer',
        'last_visited_at' => 'datetime',
        'birthday' => 'date',
        'profile_extra' => 'array',
    ];

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'member_stores')->withPivot('project_id')->withTimestamps();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function memberStamps(): HasMany
    {
        return $this->hasMany(MemberStamp::class);
    }

    public function stampTransactions(): HasMany
    {
        return $this->hasMany(MemberStampTransaction::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(MemberVisit::class);
    }

    public function memberCoupons(): HasMany
    {
        return $this->hasMany(MemberCoupon::class);
    }

    /** @return BelongsToMany<MemberTag> */
    public function tags()
    {
        return $this->belongsToMany(MemberTag::class, 'member_tag_members');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function rankHistories(): HasMany
    {
        return $this->hasMany(MemberRankHistory::class);
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function getTotalStamps(): int
    {
        return (int) $this->memberStamps()
            ->join('stamp_cards', 'stamp_cards.id', '=', 'member_stamps.stamp_card_id')
            ->sum(DB::raw(
                'member_stamps.current_stamps + COALESCE(member_stamps.cycle_count, 0) * GREATEST(COALESCE(stamp_cards.required_stamps, 1), 1)'
            ));
    }

    public function displayAddress(): string
    {
        $address = trim((string) ($this->address ?? ''));
        if ($address !== '') {
            return $address;
        }

        return trim(implode('', array_filter([
            trim((string) ($this->prefecture ?? '')),
            trim((string) ($this->city ?? '')),
            trim((string) ($this->street ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }

    /** @return array{balance: int, expires_at: ?\DateTimeImmutable, points_expiring: int, has_transactions: bool} */
    public function getPointBalance(bool $forUpdate = false): array
    {
        $query = $this->pointTransactions()->withoutGlobalScope(StoreAccessScope::class)
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

        return app(PointLedgerBalanceCalculator::class)->calculate($transactions, now());
    }

    public function getTotalPointsFromTransactions(bool $forUpdate = false): int
    {
        return $this->getPointBalance($forUpdate)['balance'];
    }

    public const POINTS_LEDGER_BACKFILL_DESCRIPTION = 'ポイント残高の整合';

    /** Backfill only members with no ledger, never resurrect spent or expired points. */
    public function syncPointsLedger(): void
    {
        DB::transaction(function (): void {
            $member = $this->newQuery()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $this->project_id)->whereKey($this->id)
                ->lockForUpdate()->firstOrFail();
            // Current reads also see a competing backfill committed after an outer transaction's snapshot.
            $balance = $member->getPointBalance(true);
            if (! $balance['has_transactions'] && (int) $member->points > 0) {
                $member->backfillEarnedPoints((int) $member->points);
            } elseif ((int) $member->points !== $balance['balance']) {
                $member->update(['points' => $balance['balance']]);
            }
            $this->points = $member->points;
            $this->syncOriginalAttribute('points');
        }, 3);
    }

    private function backfillEarnedPoints(int $points): void
    {
        if ($points <= 0) {
            return;
        }

        PointTransaction::create([
            'project_id' => $this->project_id,
            'member_id' => $this->id,
            'type' => 'earned',
            'points' => $points,
            'description' => self::POINTS_LEDGER_BACKFILL_DESCRIPTION,
            'order_id' => null,
            'expires_at' => now()->addDays(app(OrderSettings::class)->getPointsValidDays((int) $this->project_id)),
        ]);
        $this->update(['points' => $this->getTotalPointsFromTransactions(true)]);
    }

    /** 表示・判定用。既存データに台帳がない場合はカラム値を使い、読み取り時には更新しない。 */
    public function getDisplayPoints(): int
    {
        $balance = $this->getPointBalance();

        return $balance['has_transactions'] ? $balance['balance'] : max(0, (int) $this->points);
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions(true)]);
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

    /** 台帳から累計付与ポイントを再計算して保存する */
    public function recalcLifetimeGrantedPoints(): void
    {
        $total = (int) $this->pointTransactions()->withoutGlobalScope(StoreAccessScope::class)
            ->withoutGlobalScope(ProjectScope::class)
            ->get(['type', 'points'])
            ->sum(fn (PointTransaction $tx) => self::lifetimeGrantedDelta((string) $tx->type, (int) $tx->points));

        $this->update(['lifetime_granted_points' => max(0, $total)]);
    }
}
