<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampGrantRedemption extends Model
{
    use BelongsToProject;

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'stamp_grant_code_id',
        'member_id',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function code(): BelongsTo
    {
        return $this->belongsTo(StampGrantCode::class, 'stamp_grant_code_id');
    }
}
