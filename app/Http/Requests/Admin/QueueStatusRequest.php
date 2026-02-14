<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\QueueStatus;

class QueueStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valid = array_keys(QueueStatus::getValues());
        return [
            'status' => 'required|string|in:' . implode(',', $valid),
        ];
    }
}
