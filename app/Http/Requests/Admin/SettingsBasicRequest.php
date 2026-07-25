<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsBasicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_hours_start' => 'required|date_format:H:i',
            'business_hours_end' => 'required|date_format:H:i',
            'reservation_time_slots' => 'nullable|array',
            'reservation_time_slots.*' => 'date_format:H:i',
            'closed_days' => 'nullable|array',
            'closed_days.*' => 'integer|in:0,1,2,3,4,5,6',
            'closed_dates' => 'nullable|array',
            'closed_dates.*' => 'nullable|date',
            'advance_booking_days' => 'nullable|integer|min:1|max:365',
            'reservation_capacity_per_slot' => 'nullable|integer|min:1|max:999',
            'order_mode' => 'required|string|in:both,mobile_only,in_store_only',
            'order_line_notify_enabled' => 'nullable|in:0,1',
            'order_ready_line_notify_enabled' => 'nullable|in:0,1',
        ];
    }

    /**
     * @return array{
     *     business_hours_start: string,
     *     business_hours_end: string,
     *     reservation_time_slots?: array<int, string>,
     *     closed_days?: array<int, int>,
     *     closed_dates?: array<int, string|null>,
     *     advance_booking_days?: int|null,
     *     reservation_capacity_per_slot?: int|null,
     *     order_mode: string,
     *     order_line_notify_enabled: bool,
     *     order_ready_line_notify_enabled: bool
     * }
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        if ($key !== null) {
            return $validated;
        }
        $validated['order_line_notify_enabled'] = $this->boolean('order_line_notify_enabled');
        $validated['order_ready_line_notify_enabled'] = $this->boolean('order_ready_line_notify_enabled');

        return $validated;
    }
}
