<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAudienceGroup extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'criteria',
    ];

    protected $casts = [
        'criteria' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
