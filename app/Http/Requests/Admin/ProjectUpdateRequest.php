<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = (int) $this->route('id');

        return [
            'project_key' => [
                'required',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('projects', 'project_key')->ignore($projectId),
            ],
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
            'is_active' => (bool) ($this->validated('is_active') ?? false),
        ];
    }
}
