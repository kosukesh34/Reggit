<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use BelongsToProject;

    protected $hidden = ['auth_token', 'push_token'];

    protected $fillable = [
        'project_id',
        'user_id',
        'platform',
        'device_identifier',
        'push_token',
        'os_version',
        'app_version',
        'last_used_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'auth_token' => 'encrypted',
        'last_used_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function authenticates(?string $token): bool
    {
        return is_string($this->auth_token) && $this->auth_token !== ''
            && is_string($token) && $token !== '' && hash_equals($this->auth_token, $token);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
