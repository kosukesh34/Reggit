<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<MemberTag> */
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
        return (int) $this->pointTransactions()->sum('points');
    }

    public function addPoints(int $points, ?string $description = null, ?int $orderId = null): void
    {
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
            'expires_at' => $expiresAt,
        ]);
        $this->recalcPointsFromTransactions();
    }

    public function usePoints(int $points, ?string $description = null, ?int $orderId = null): void
    {
        if ($points <= 0) {
            return;
        }
        PointTransaction::create([
            'project_id' => $this->project_id,
            'member_id' => $this->id,
            'type' => 'used',
            'points' => -abs($points),
            'description' => $description,
            'order_id' => $orderId,
        ]);
        $this->recalcPointsFromTransactions();
    }

    public function recalcPointsFromTransactions(): void
    {
        $this->update(['points' => $this->getTotalPointsFromTransactions()]);
    }
}
