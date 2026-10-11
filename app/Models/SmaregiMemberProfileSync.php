<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmaregiMemberProfileSync extends Model
{
    protected $primaryKey = 'member_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'body' => 'array',
        'field_keys' => 'array',
        'retry_at' => 'datetime',
        'leased_until' => 'datetime',
        'delivered_at' => 'datetime',
    ];
}
