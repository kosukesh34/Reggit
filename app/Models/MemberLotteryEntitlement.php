<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLotteryEntitlement extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_id',
        'reward_type',
        'source_type',
        'source_id',
        'is_done',
        'done_at',
        'won_coupon_id',
        'member_coupon_id',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function wonCoupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'won_coupon_id');
    }

    public function memberCoupon(): BelongsTo
    {
        return $this->belongsTo(MemberCoupon::class, 'member_coupon_id');
    }

    public function isPlayable(): bool
    {
        if ($this->is_done === true) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
