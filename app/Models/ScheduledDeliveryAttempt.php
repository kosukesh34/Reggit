<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A durable scheduled occurrence, separate from successful member sends. */
class ScheduledDeliveryAttempt extends Model
{
    /** @var list<string> */
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'failed_at' => 'datetime',
        'delivery_snapshot' => 'array',
        'target_member_ids' => 'array',
        'attempts' => 'integer',
    ];
}
