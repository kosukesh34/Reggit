<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmaregiMemberProfileSync extends Model
{
    protected $primaryKey = 'member_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['member_id', 'project_id', 'customer_id', 'contract_id', 'body', 'field_keys', 'request_token', 'retry_at', 'leased_until', 'lease_token', 'delivered_at'];

    protected $casts = [
        'body' => 'array',
        'field_keys' => 'array',
        'retry_at' => 'datetime',
        'leased_until' => 'datetime',
        'delivered_at' => 'datetime',
    ];
}
