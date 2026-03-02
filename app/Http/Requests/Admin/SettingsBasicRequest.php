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
            'reservation_time_slots' => 'required|array',
            'reservation_time_slots.*' => 'date_format:H:i',
            'closed_days' => 'nullable|array',
            'closed_days.*' => 'string|in:sun,mon,tue,wed,thu,fri,sat',
            'closed_dates' => 'nullable|array',
            'closed_dates.*' => 'nullable|date',
            'advance_booking_days' => 'required|integer|min:1|max:365',
            'reservation_capacity_per_slot' => 'required|integer|min:1|max:999',
            'order_mode' => 'required|string|in:both,mobile_only,in_store_only',
        ];
    }
}
