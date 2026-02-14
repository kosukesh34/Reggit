<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_key' => 'required|string|max:64|alpha_dash|unique:projects,project_key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function toProjectPayload(): array
    {
        return [
            'project_key' => $this->validated('project_key'),
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'is_active' => (bool) ($this->validated('is_active') ?? true),
        ];
    }
}
