<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMemberPointsSetting extends Model
{
    protected $table = 'project_member_points_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'point_earn_rate',
        'points_per_order',
        'stamp_earn_per_amount',
        'order_stamp_card_id',
        'points_valid_days',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'point_earn_rate' => 100,
            'points_per_order' => 0,
            'stamp_earn_per_amount' => 0,
            'points_valid_days' => 365,
        ];
    }
}
