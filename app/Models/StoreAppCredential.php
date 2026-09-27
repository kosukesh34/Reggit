<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreAppCredential extends Model
{
    protected $fillable = [
        'project_id', 'admin_user_id', 'personal_access_token_id', 'app',
        'enrollment_token_hash', 'device_key_hash', 'device_name',
        'enrollment_created_at', 'claimed_at',
    ];

    protected $casts = [
        'enrollment_created_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
