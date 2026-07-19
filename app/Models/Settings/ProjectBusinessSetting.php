<?php

namespace App\Models\Settings;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBusinessSetting extends Model
{
    protected $table = 'project_business_settings';

    protected $primaryKey = 'project_id';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'business_hours_start',
        'business_hours_end',
        'reservation_time_slots',
        'closed_days',
        'closed_dates',
        'advance_booking_days',
        'reservation_capacity_per_slot',
        'queue_avg_serve_minutes',
        'queue_require_membership',
        'queue_channel_line',
        'queue_channel_native',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reservation_time_slots' => 'array',
        'closed_days' => 'array',
        'closed_dates' => 'array',
        'queue_require_membership' => 'boolean',
        'queue_channel_line' => 'boolean',
        'queue_channel_native' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return array<string, mixed> */
    public static function getDefaultAttributes(): array
    {
        return [
            'business_hours_start' => '10:00',
            'business_hours_end' => '22:00',
            'reservation_time_slots' => ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'],
            'closed_days' => [],
            'closed_dates' => [],
            'advance_booking_days' => 30,
            'reservation_capacity_per_slot' => 20,
            'queue_avg_serve_minutes' => 10,
            'queue_require_membership' => false,
            'queue_channel_line' => true,
            'queue_channel_native' => false,
        ];
    }
}
