<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberSubscription extends Model
{
    use BelongsToProject;

    /** Stripe の status のうち、解約済みとして扱うもの */
    public const ENDED_STATUSES = ['canceled', 'incomplete_expired'];

    protected $fillable = [
        'project_id',
        'member_id',
        'product_id',
        'store_id',
        'payment_setting_id',
        'stripe_subscription_id',
        'name',
        'status',
        'cancel_at_period_end',
        'current_period_end',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'current_period_end' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MemberTicket::class);
    }

    public function isEnded(): bool
    {
        return in_array($this->status, self::ENDED_STATUSES, true);
    }
}
