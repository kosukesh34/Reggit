<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancelledAnnouncementPush extends Model
{
    protected $fillable = [
        'project_id',
        'announcement_id',
        'title',
        'cancelled_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'project_id' => 'integer',
        'announcement_id' => 'integer',
        'cancelled_at' => 'datetime',
    ];
}
