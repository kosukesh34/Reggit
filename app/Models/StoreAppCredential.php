<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreAppCredential extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'store_id', 'admin_user_id', 'personal_access_token_id', 'app',
        'enrollment_token_hash', 'device_key_hash', 'device_name',
        'enrollment_created_at', 'claimed_at',
    ];

    protected $casts = [
        'enrollment_created_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
