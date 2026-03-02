<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;

class ReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valid = array_keys(ReservationStatus::getValues());

        return [
            'status' => 'required|string|in:'.implode(',', $valid),
        ];
    }
}
