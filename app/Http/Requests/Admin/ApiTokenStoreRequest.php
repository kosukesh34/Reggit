<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApiTokenStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:100',
            'expires_at' => 'nullable|date',
        ];
    }

    public function toPayload(): array
    {
        return [
            'project_id' => (int) $this->validated('project_id'),
            'name' => $this->validated('name'),
            'expires_at' => $this->validated('expires_at'),
        ];
    }
}
