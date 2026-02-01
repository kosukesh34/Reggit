<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStamp extends Model
{
    use BelongsToProject;

    protected $fillable = ['project_id', 'member_id', 'stamp_card_id', 'current_stamps', 'completed_at'];

    protected function casts(): array
    {
        return [
            'current_stamps' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

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
