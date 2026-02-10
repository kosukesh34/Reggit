<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use BelongsToProject;

    /** 手動発行（管理画面で会員に付与） */
    public const ISSUE_TRIGGER_MANUAL = 'manual';
    /** 初回登録時に自動発行 */
    public const ISSUE_TRIGGER_FIRST_REGISTRATION = 'first_registration';
    /** スタンプカード達成時に自動発行 */
    public const ISSUE_TRIGGER_STAMP_COMPLETED = 'stamp_completed';

    protected $fillable = [
        'project_id', 'code', 'name', 'image_url', 'type', 'value', 'min_order_amount',
        'usage_limit', 'used_count', 'start_at', 'end_at', 'is_active',
        'issue_trigger', 'stamp_card_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
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
