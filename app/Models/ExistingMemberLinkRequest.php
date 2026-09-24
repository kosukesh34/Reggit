<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class ExistingMemberLinkRequest extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'user_id',
        'smaregi_customer_id',
        'email',
        'token',
        'expires_at',
        'confirmed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];
}
