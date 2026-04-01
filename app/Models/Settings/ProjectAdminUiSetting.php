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
        'frontend_header_text',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, bool|int|float|string|array|object|null> */
    public static function getDefaultAttributes(): array
    {
        return [];
    }
}
