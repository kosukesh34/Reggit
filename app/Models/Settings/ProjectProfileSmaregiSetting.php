<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProfileSmaregiSetting extends Model
{
    protected $table = 'project_profile_smaregi_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'profile_field_settings',
        'member_registration_mode',
        'smaregi_contract_id',
        'smaregi_client_id',
        'smaregi_client_secret',
        'smaregi_store_id',
        'smaregi_terminal_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'profile_field_settings' => 'array',
        'smaregi_client_secret' => 'encrypted',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'member_registration_mode' => 'optional',
        ];
    }
}
