<?php

namespace App\Models;

use App\Domain\Survey\ValueObjects\SurveyDefinition;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'questions',
        'reward_points',
        'invite_trigger',
        'invite_delay_days',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'questions' => 'array',
        'reward_points' => 'integer',
        'invite_delay_days' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function toDefinition(): SurveyDefinition
    {
        return new SurveyDefinition(
            id: (int) $this->id,
            projectId: (int) $this->project_id,
            title: (string) $this->title,
            description: $this->description,
            questions: array_values($this->questions ?? []),
            rewardPoints: (int) $this->reward_points,
            inviteTrigger: $this->invite_trigger,
            inviteDelayDays: (int) ($this->invite_delay_days ?? 1),
            isActive: (bool) $this->is_active,
            startsAt: $this->starts_at?->toDateTimeImmutable(),
            endsAt: $this->ends_at?->toDateTimeImmutable(),
            createdAt: ($this->created_at ?? now())->toDateTimeImmutable(),
        );
    }
}
