<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class ProjectRfmSetting extends Model
{
    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = ['project_id', 'recency_active_days', 'recency_sleeping_days', 'frequency_high', 'monetary_high'];

    protected $casts = [
        'recency_active_days' => 'integer',
        'recency_sleeping_days' => 'integer',
        'frequency_high' => 'integer',
        'monetary_high' => 'integer',
    ];

    public static function forProject(int $projectId): self
    {
        return static::find($projectId) ?? new static([
            'project_id' => $projectId,
            'recency_active_days' => 30,
            'recency_sleeping_days' => 90,
            'frequency_high' => 5,
            'monetary_high' => 10000,
        ]);
    }
}
