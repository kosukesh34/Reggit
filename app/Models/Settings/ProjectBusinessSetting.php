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
        'reservation_capacity_by_people',
        'reservation_max_party_size',
        'reservation_same_day',
        'reservation_lead_minutes',
        'reservation_change_deadline_hours',
        'reservation_reminder_hours',
        'reservation_reminder_second_hours',
        'reservation_reminder_message',
        'queue_avg_serve_minutes',
        'queue_require_membership',
        'queue_channel_line',
        'queue_channel_native',
        'shop_shipping_fee',
        'shop_shipping_fees_by_prefecture',
        'shop_legal_notice',
        'shop_free_shipping_threshold',
        'shop_delivery_date_enabled',
        'shop_delivery_lead_days',
        'shop_delivery_range_days',
        'shop_delivery_time_slots',
        'shop_invoice_registration_number',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reservation_time_slots' => 'array',
        'closed_days' => 'array',
        'closed_dates' => 'array',
        'reservation_capacity_by_people' => 'boolean',
        'reservation_max_party_size' => 'integer',
        'reservation_same_day' => 'boolean',
        'reservation_lead_minutes' => 'integer',
        'reservation_change_deadline_hours' => 'integer',
        'reservation_reminder_hours' => 'integer',
        'reservation_reminder_second_hours' => 'integer',
        'queue_require_membership' => 'boolean',
        'queue_channel_line' => 'boolean',
        'queue_channel_native' => 'boolean',
        'shop_shipping_fee' => 'integer',
        'shop_shipping_fees_by_prefecture' => 'array',
        'shop_legal_notice' => 'array',
        'shop_free_shipping_threshold' => 'integer',
        'shop_delivery_date_enabled' => 'boolean',
        'shop_delivery_lead_days' => 'integer',
        'shop_delivery_range_days' => 'integer',
        'shop_delivery_time_slots' => 'array',
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
            'reservation_capacity_by_people' => false,
            'reservation_max_party_size' => 10,
            'reservation_same_day' => false,
            'reservation_lead_minutes' => 0,
            'reservation_change_deadline_hours' => 0,
            'reservation_reminder_hours' => 0,
            'reservation_reminder_second_hours' => 0,
            'reservation_reminder_message' => null,
            'queue_avg_serve_minutes' => 10,
            'queue_require_membership' => false,
            'queue_channel_line' => true,
            'queue_channel_native' => false,
            'shop_shipping_fee' => 500,
            'shop_shipping_fees_by_prefecture' => [],
            'shop_legal_notice' => [],
            'shop_free_shipping_threshold' => null,
            'shop_delivery_date_enabled' => false,
            'shop_delivery_lead_days' => 3,
            'shop_delivery_range_days' => 14,
            'shop_delivery_time_slots' => [],
            'shop_invoice_registration_number' => null,
        ];
    }
}
