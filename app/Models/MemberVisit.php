<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberVisit extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_id',
        'visited_at',
        'source',
        'order_id',
        'note',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
