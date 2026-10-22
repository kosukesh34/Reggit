<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmaregiRankSettingsSync extends Model
{
    protected $primaryKey = 'project_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['project_id', 'request_token', 'retry_at', 'leased_until', 'lease_token', 'delivered_at'];

    protected $casts = ['retry_at' => 'datetime', 'leased_until' => 'datetime', 'delivered_at' => 'datetime'];
}
