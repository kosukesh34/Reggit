<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMemberRankSetting extends Model
{
    protected $table = 'project_member_rank_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'member_rank_basis',
        'use_stamps',
        'use_transaction_stamps',
        'use_points',
        'point_usage_scope',
        'member_rank_aggregation_type',
        'member_rank_period_days',
        'is_rank_down_enabled',
        'member_code_type',
        'member_code_format',
        'member_code_prefix',
        'member_code_length',
        'member_code_next_number',
        'show_points_display',
        'show_rank_progress',
        'show_member_card_for_guest',
        'member_card_design',
        'member_card_display_type',
        'member_card_code_type',
        'member_rank_settings',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'use_stamps' => 'boolean',
        'use_transaction_stamps' => 'boolean',
        'use_points' => 'boolean',
        'is_rank_down_enabled' => 'boolean',
        'member_rank_period_days' => 'integer',
        'show_points_display' => 'boolean',
        'show_rank_progress' => 'boolean',
        'show_member_card_for_guest' => 'boolean',
        'member_rank_settings' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'member_rank_basis' => 'points',
            'use_stamps' => true,
            'use_transaction_stamps' => true,
            'use_points' => true,
            'point_usage_scope' => 'app',
            'member_rank_aggregation_type' => 'total',
            'member_rank_period_days' => 365,
            'is_rank_down_enabled' => false,
            'member_code_type' => 'random',
            'member_code_format' => 'numeric',
            'member_code_length' => 10,
            'member_code_prefix' => '',
            'member_code_next_number' => 1,
            'show_points_display' => true,
            'show_rank_progress' => true,
            'show_member_card_for_guest' => true,
            'member_card_design' => 'default',
            'member_card_display_type' => 'card',
            'member_card_code_type' => 'barcode',
        ];
    }
}
