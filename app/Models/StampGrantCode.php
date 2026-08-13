<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StampGrantCode extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'stamp_card_id',
        'token',
        'amount',
        'usage_limit',
        'expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'amount' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(StampGrantRedemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now());
    }

    public function openUrl(): string
    {
        return url('/g/'.$this->token);
    }
}
