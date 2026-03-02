<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valid = array_keys(OrderStatus::getValues());

        return [
            'status' => 'required|string|in:'.implode(',', $valid),
        ];
    }
}
