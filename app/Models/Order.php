<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use App\Support\DailySequenceNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToProject, HasFactory;

    protected $hidden = ['payment_session', 'payment_setting_id'];

    protected $fillable = [
        'project_id', 'store_id',
        'user_id',
        'order_number',
        'order_type',
        'total_amount',
        'status',
        'payment_method',
        'notes',
        'points_used',
        'points_earned',
        'smaregi_points_synced_at',
        'smaregi_points_sync_started_at',
        'smaregi_transaction_head_id',
        'smaregi_transaction_synced_at',
        'smaregi_transaction_sync_started_at',
        'smaregi_layaway_head_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payment_session' => 'encrypted:array',
        'total_amount' => 'decimal:2',
        'points_used' => 'integer',
        'points_earned' => 'integer',
        'smaregi_points_synced_at' => 'datetime',
        'smaregi_points_sync_started_at' => 'datetime',
        'smaregi_transaction_synced_at' => 'datetime',
        'smaregi_transaction_sync_started_at' => 'datetime',
    ];

    public function getPaymentBrandLabelAttribute(): string
    {
        return match ($this->paid_payment_brand ?? $this->payment_brand ?? $this->payment_method) {
            'card', 'stripe' => 'クレジットカード',
            'paypay' => 'PayPay',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
            'cash' => '現金',
            'points' => 'ポイント',
            default => 'その他',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function formattedOrderNumber(): string
    {
        return DailySequenceNumber::formatStoredForDisplay($this->order_number);
    }
}
