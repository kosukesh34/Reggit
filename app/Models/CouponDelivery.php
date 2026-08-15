<?php

namespace App\Models;

use App\Enums\CouponDeliveryType;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponDelivery extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'coupon_id',
        'delivery_type',
        'status',
        'scheduled_at',
        'sent_at',
        'target_member_ids',
        'issued_member_ids',
        'segment_criteria',
        'issued_count',
        'send_notification',
        'error_message',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'target_member_ids' => 'array',
        'issued_member_ids' => 'array',
        'segment_criteria' => 'array',
        'issued_count' => 'integer',
        'send_notification' => 'boolean',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'send_notification' => true,
    ];

    public function isScheduledType(): bool
    {
        return CouponDeliveryType::isScheduled((string) $this->delivery_type);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
