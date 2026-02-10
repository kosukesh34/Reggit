<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StampStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'required_stamps' => 'required|integer|min:1|max:100',
            'reward_description' => 'nullable|string|max:500',
            'image_url' => 'nullable|url|max:500',
            'is_active' => 'nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
