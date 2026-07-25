<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenDevice extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'push_token',
        'platform',
        'device_name',
        'last_seen_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
