<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPasskey extends Model
{
    protected $fillable = [
        'name',
        'credential_id',
        'credential',
        'last_used_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'credential' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
