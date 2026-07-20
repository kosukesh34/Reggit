<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberRankHistory extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_id',
        'from_rank',
        'to_rank',
        'from_rank_label',
        'to_rank_label',
        'achieved_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'achieved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
