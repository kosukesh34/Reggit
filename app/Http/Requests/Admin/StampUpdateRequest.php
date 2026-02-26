<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StampUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'required_stamps' => 'required|integer|min:1|max:999',
            'reward_description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|url|max:500',
            'layout_style' => 'nullable|string|in:grid,row,circle,minimal,card',
            'valid_days' => 'nullable|integer|min:1|max:3650',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }
}
