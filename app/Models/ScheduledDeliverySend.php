<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledDeliverySend extends Model
{
    protected $fillable = [
        'after_registration_delivery_id',
        'member_id',
        'send_key',
        'sent_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(AfterRegistrationDelivery::class, 'after_registration_delivery_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
