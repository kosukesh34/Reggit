<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApiTokenUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->validated('name'),
            'expires_at' => $this->validated('expires_at'),
            'is_active' => (bool) ($this->validated('is_active') ?? false),
        ];
    }
}
