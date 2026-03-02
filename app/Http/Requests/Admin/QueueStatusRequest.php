<?php

namespace App\Http\Requests\Admin;

use App\Enums\QueueStatus;
use Illuminate\Foundation\Http\FormRequest;

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
            'status' => 'required|string|in:'.implode(',', $valid),
        ];
    }
}
