<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStamp extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id', 'member_id', 'stamp_card_id', 'current_stamps', 'cycle_count', 'completed_at', 'valid_until',
    ];

    public int $cyclesCompletedNow = 0;

    public ?string $issueSourceType = null;

    public ?string $issueSourceId = null;

    /** @var array<string, string> */
    protected $casts = [
        'current_stamps' => 'integer',
        'cycle_count' => 'integer',
        'completed_at' => 'datetime',
        'valid_until' => 'datetime',
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
