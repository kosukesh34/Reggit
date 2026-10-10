<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmaregiMemberRankSync extends Model
{
    protected $primaryKey = 'member_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'retry_at' => 'datetime',
        'leased_until' => 'datetime',
    ];
}
