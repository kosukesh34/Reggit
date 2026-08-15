<?php

namespace App\Models;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\PointTransactionType;
use App\Models\Concerns\BelongsToProject;
use App\Models\Scopes\ProjectScope;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use BelongsToProject, HasFactory, SoftDeletes;

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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function memberStamps()
    {
        return $this->hasMany(MemberStamp::class);
    }

    public function stampTransactions()
    {
        return $this->hasMany(MemberStampTransaction::class);
    }

    public function visits()
    {
        return $this->hasMany(MemberVisit::class);
    }

    public function memberCoupons()
    {
        return $this->hasMany(MemberCoupon::class);
    }

    /** @return BelongsToMany<MemberTag> */
    public function tags()
    {
        return $this->belongsToMany(MemberTag::class, 'member_tag_members');
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function rankHistories()
    {
        return $this->hasMany(MemberRankHistory::class);
    }

    public function queueEntries()
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function getTotalStamps(): int
    {
        return (int) $this->memberStamps()->sum('current_stamps');
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

    public function getTotalPointsFromTransactions(): int
    {
        return (int) $this->pointTransactions()
            ->withoutGlobalScope(ProjectScope::class)
            ->sum('points');
    }

    /** members.points カラムのみ残っている古いデータを point_transactions に寄せる */
    public const POINTS_LEDGER_BACKFILL_DESCRIPTION = 'ポイント残高の整合';

    /**
     * 表示用カラムと取引合計を一致させる。カラムのみ残っている場合は取引を補完する。
     */
    public function syncPointsLedger(): void
    {
        $columnPoints = max(0, (int) $this->points);
        $ledgerPoints = $this->getTotalPointsFromTransactions();

        if ($ledgerPoints === $columnPoints) {
            return;
        }

        if ($ledgerPoints === 0 && $columnPoints > 0) {
            $this->backfillEarnedPoints($columnPoints);

            return;
        }

        if ($columnPoints > $ledgerPoints) {
            if ($ledgerPoints > 0) {
                $this->recalcPointsFromTransactions();

                return;
            }

            $this->backfillEarnedPoints($columnPoints - $ledgerPoints);

            return;
        }

        $this->recalcPointsFromTransactions();
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
            'expires_at' => now()->addDays(app(ShopSettingRepository::class)->getPointsValidDays((int) $this->project_id)),
        ]);
        $this->recalcPointsFromTransactions();
    }

    /** 表示・判定用。台帳とカラムがずれていれば整合してから返す */
    public function getDisplayPoints(): int
    {
        $ledgerPoints = $this->getTotalPointsFromTransactions();
        if ((int) $this->points !== $ledgerPoints) {
            $this->syncPointsLedger();
            $this->refresh();

            return $this->getTotalPointsFromTransactions();
        }

        return $ledgerPoints;
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions()]);
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
        $total = (int) $this->pointTransactions()
            ->withoutGlobalScope(ProjectScope::class)
            ->get(['type', 'points'])
            ->sum(fn (PointTransaction $tx) => self::lifetimeGrantedDelta((string) $tx->type, (int) $tx->points));

        $this->update(['lifetime_granted_points' => max(0, $total)]);
    }
}
