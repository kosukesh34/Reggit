<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLineLiffSetting extends Model
{
    protected $table = 'project_line_liff_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'line_primary_color',
        'line_primary_dark',
        'line_success_color',
        'line_danger_color',
        'line_liff_id',
        'line_liff_url',
        'liff_builder_blocks',
        'liff_tab_layouts',
        'liff_tab_settings',
        'liff_saved_screens',
        'liff_custom_css',
        'order_mode',
        'frontend_menu_items',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'liff_builder_blocks' => 'array',
        'liff_tab_layouts' => 'array',
        'liff_tab_settings' => 'array',
        'liff_saved_screens' => 'array',
        'frontend_menu_items' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'line_primary_color' => '#000000',
            'line_primary_dark' => '#333333',
            'line_success_color' => '#000000',
            'line_danger_color' => '#dc3545',
            'order_mode' => 'both',
        ];
    }
}
