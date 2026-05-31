<?php

namespace App\Models;

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
        'status',
        'rank',
        'birthday',
        'address',
        'postal_code',
        'prefecture',
        'city',
        'street',
        'sex',
        'profile_extra',
        'memo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'points' => 'integer',
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

    public function queueEntries()
    {
        return $this->hasMany(QueueEntry::class);
    }

    public function getTotalStamps(): int
    {
        return (int) $this->memberStamps()->sum('current_stamps');
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
            $this->addPoints($columnPoints, self::POINTS_LEDGER_BACKFILL_DESCRIPTION);

            return;
        }

        if ($columnPoints > $ledgerPoints) {
            // 台帳が正でカラムだけ古い場合は加算せずキャッシュを合わせる
            if ($ledgerPoints > 0) {
                $this->recalcPointsFromTransactions();

                return;
            }

            $this->addPoints($columnPoints - $ledgerPoints, self::POINTS_LEDGER_BACKFILL_DESCRIPTION);

            return;
        }

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

    public function addPoints(
        int $points,
        ?string $description = null,
        ?int $orderId = null,
        ?string $smaregiTransactionHeadId = null
    ): void {
        if ($points <= 0) {
            return;
        }
        $expiresAt = now()->addDays(ShopSetting::getPointsValidDays($this->project_id));
        PointTransaction::create([
            'project_id' => $this->project_id,
            'member_id' => $this->id,
            'type' => 'earned',
            'points' => $points,
            'description' => $description,
            'order_id' => $orderId,
            'smaregi_transaction_head_id' => $this->normalizeSmaregiTransactionHeadId($smaregiTransactionHeadId),
            'expires_at' => $expiresAt,
        ]);
        $this->recalcPointsFromTransactions();
    }

    public function usePoints(
        int $points,
        ?string $description = null,
        ?int $orderId = null,
        ?string $smaregiTransactionHeadId = null
    ): void {
        if ($points <= 0) {
            return;
        }
        $available = $this->getTotalPointsFromTransactions();
        if ($available <= 0) {
            return;
        }
        $points = min($points, $available);
        PointTransaction::create([
            'project_id' => $this->project_id,
            'member_id' => $this->id,
            'type' => 'used',
            'points' => -abs($points),
            'description' => $description,
            'order_id' => $orderId,
            'smaregi_transaction_head_id' => $this->normalizeSmaregiTransactionHeadId($smaregiTransactionHeadId),
        ]);
        $this->recalcPointsFromTransactions();
    }

    private function normalizeSmaregiTransactionHeadId(?string $smaregiTransactionHeadId): ?string
    {
        if ($smaregiTransactionHeadId === null) {
            return null;
        }
        $normalized = trim($smaregiTransactionHeadId);

        return $normalized !== '' ? $normalized : null;
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions()]);
    }
}
