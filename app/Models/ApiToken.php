<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'token_hash',
        'is_active',
        'last_used_at',
        'expires_at',
        'scope',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function issue(Project $project, string $name, ?\DateTimeInterface $expiresAt = null, string $scope = 'general'): array
    {
        $plain = bin2hex(random_bytes(32));
        $token = self::create([
            'project_id' => $project->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'is_active' => true,
            'expires_at' => $expiresAt,
            'scope' => $scope,
        ]);

        return ['token' => $token, 'plain' => $plain];
    }
}
