<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAnnouncementSetting extends Model
{
    protected $table = 'project_announcement_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'announcement_default_pinned',
        'announcement_default_expiry_days',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'announcement_default_pinned' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'announcement_default_pinned' => false,
            'announcement_default_expiry_days' => 0,
        ];
    }
}
