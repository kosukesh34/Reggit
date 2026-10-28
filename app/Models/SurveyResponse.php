<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 会員ごとの回答。回答依頼だけ送った段階では answered_at が NULL。 */
class SurveyResponse extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'survey_id',
        'member_id',
        'answers',
        'nps_score',
        'invited_at',
        'answered_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'answers' => 'array',
        'nps_score' => 'integer',
        'invited_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
