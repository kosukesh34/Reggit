<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmaregiMemberRankSync extends Model
{
    protected $primaryKey = 'member_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['member_id', 'project_id', 'request_token', 'retry_at', 'leased_until', 'lease_token'];

    protected $casts = [
        'retry_at' => 'datetime',
        'leased_until' => 'datetime',
    ];
}
