<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class CouponDeviceIssue extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'coupon_id', 'member_id', 'member_coupon_id', 'device_identifier', 'issued_at',
    ];

    protected $casts = ['issued_at' => 'datetime'];
}
