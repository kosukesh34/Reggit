<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberTicket extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_id',
        'order_id',
        'order_item_id',
        'product_id',
        'name',
        'total_uses',
        'used_count',
        'expires_at',
        'revoked_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_uses' => 'integer',
        'used_count' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uses(): HasMany
    {
        return $this->hasMany(MemberTicketUse::class)->orderByDesc('used_at')->orderByDesc('id');
    }

    public function remainingUses(): int
    {
        return max(0, $this->total_uses - $this->used_count);
    }

    /** active / used_up / expired / revoked */
    public function status(): string
    {
        if ($this->revoked_at !== null) {
            return 'revoked';
        }
        if ($this->remainingUses() === 0) {
            return 'used_up';
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }
}
