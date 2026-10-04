<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;

class PointGrantCode extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'token', 'points', 'usage_quota', 'expires_at'];

    /** @var array<string, string> */
    protected $casts = ['points' => 'integer', 'usage_quota' => 'integer', 'expires_at' => 'immutable_datetime'];
}
