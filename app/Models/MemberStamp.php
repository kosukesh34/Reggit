<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemberStamp extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = ['project_id', 'member_id', 'stamp_card_id', 'current_stamps', 'completed_at'];

    /** @var array<string, string> */
    protected $casts = [
        'current_stamps' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }
}
