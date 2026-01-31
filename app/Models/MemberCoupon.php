<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MemberCoupon extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_id',
        'coupon_id',
        'coupon_name_snapshot',
        'status',
        'issued_at',
        'valid_from',
        'valid_until',
        'used_at',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public const STATUS_UNUSED = 'unused';
    public const STATUS_USED = 'used';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function redemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class);
    }

    public function isUnused(): bool
    {
        return $this->status === self::STATUS_UNUSED;
    }

    public function isUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }
}
