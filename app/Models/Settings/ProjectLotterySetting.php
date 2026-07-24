<?php

namespace App\Models\Settings;

use App\Enums\LotteryGrantTrigger;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLotterySetting extends Model
{
    protected $table = 'project_lottery_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'gacha_enabled',
        'scratch_enabled',
        'grant_trigger',
        'min_amount',
        'entitlement_expires_hours',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'gacha_enabled' => 'boolean',
        'scratch_enabled' => 'boolean',
        'min_amount' => 'decimal:2',
        'entitlement_expires_hours' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'gacha_enabled' => false,
            'scratch_enabled' => false,
            'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
            'min_amount' => null,
            'entitlement_expires_hours' => null,
        ];
    }
}
