<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberTransferCode extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'user_id',
        'issued_by_admin_user_id',
        'code_hash',
        'expires_at',
        'consumed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
