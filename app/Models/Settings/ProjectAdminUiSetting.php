<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAdminUiSetting extends Model
{
    protected $table = 'project_admin_ui_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'admin_sidebar_color',
        'admin_header_color',
        'admin_primary_color',
        'stamp_default_image_url',
        'coupon_default_image_url',
        'frontend_logo_url',
        'navigation_header_image_url',
        'navigation_header_background_color',
        'home_image_url',
        'home_image_urls',
        'home_menu_layout',
        'home_content',
        'home_show_member_card',
        'native_chrome_colors',
        'product_display_layout',
        'native_navigation_items',
        'member_show_rank_progress',
        'member_show_point_history',
        'member_show_coupon_activity',
        'frontend_header_text',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'home_show_member_card' => 'boolean',
        'native_chrome_colors' => 'array',
        'home_image_urls' => 'array',
        'home_content' => 'array',
        'native_navigation_items' => 'array',
        'member_show_rank_progress' => 'boolean',
        'member_show_point_history' => 'boolean',
        'member_show_coupon_activity' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [];
    }
}
