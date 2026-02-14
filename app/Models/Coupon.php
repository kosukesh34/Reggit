<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use BelongsToProject, HasFactory;

    public const ISSUE_TRIGGER_MANUAL = 'manual';
    public const ISSUE_TRIGGER_FIRST_REGISTRATION = 'first_registration';
    public const ISSUE_TRIGGER_STAMP_COMPLETED = 'stamp_completed';

    protected $fillable = [
        'project_id', 'code', 'name', 'image_url', 'type', 'value', 'min_order_amount',
        'usage_limit', 'used_count', 'start_at', 'end_at', 'is_active', 'issue_trigger', 'stamp_card_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function memberCoupons(): HasMany
    {
        return $this->hasMany(MemberCoupon::class);
    }
}
