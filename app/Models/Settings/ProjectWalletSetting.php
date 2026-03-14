<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWalletSetting extends Model
{
    protected $table = 'project_wallet_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'wallet_google_program_logo_url',
        'wallet_google_hero_image_url',
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
