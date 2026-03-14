<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reserved_at_date' => 'required|date',
            'reserved_at_time' => 'required|date_format:H:i',
            'number_of_people' => 'required|integer|min:1|max:100',
            'notes' => 'nullable|string|max:500',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'reserved_at_date.required' => '予約日を入力してください。',
            'reserved_at_time.required' => '予約時間を入力してください。',
            'number_of_people.required' => '人数を入力してください。',
            'number_of_people.min' => '人数は1以上を入力してください。',
        ];
    }
}
