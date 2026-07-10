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
        app(\App\Domain\Member\Repositories\MemberRepository::class)
            ->syncPointsLedger((int) $this->id, (int) $this->project_id);
        $this->refresh();
    }

    /** 表示・判定用。台帳とカラムがずれていれば整合してから返す */
    public function getDisplayPoints(): int
    {
        $ledgerPoints = $this->getTotalPointsFromTransactions();
        if ((int) $this->points !== $ledgerPoints) {
            $this->syncPointsLedger();

            return $this->getTotalPointsFromTransactions();
        }

        return $ledgerPoints;
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions()]);
    }
}
