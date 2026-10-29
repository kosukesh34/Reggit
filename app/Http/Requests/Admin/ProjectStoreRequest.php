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
            'service_status' => 'sometimes|required|in:active,unpaid,cancelled',
            'plan' => 'required|string|in:normal,distribution,premium',
            'line_plan_enabled' => 'nullable|boolean',
            'max_active_stores' => 'sometimes|integer|min:1|max:1000',
            'line_liff_id' => 'required|string|max:128',
            'line_liff_url' => 'required|url|max:2048',
            'frontend_header_text' => 'required|string|max:255',
            'frontend_logo' => 'nullable|image|max:2048',
            'smaregi_contract_id' => 'nullable|string|max:255',
            'smaregi_client_id' => 'nullable|string|max:255',
            'smaregi_client_secret' => 'nullable|string|max:512',
        ];
    }

    public function toProjectPayload(): array
    {
        return [
            'project_key' => $this->validated('project_key'),
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'is_active' => (bool) ($this->validated('is_active') ?? true),
            'plan' => $this->validated('plan') ?? 'normal',
            'line_plan_enabled' => $this->boolean('line_plan_enabled'),
            'max_active_stores' => (int) ($this->validated('max_active_stores') ?? 1),
            ...($this->has('service_status') ? ['service_status' => $this->validated('service_status')] : []),
        ];
    }

    public function toProjectSettingsPayload(): array
    {
        return [
            'line_liff_id' => trim((string) $this->validated('line_liff_id')),
            'line_liff_url' => trim((string) $this->validated('line_liff_url')),
            'frontend_header_text' => trim((string) $this->validated('frontend_header_text')),
            'smaregi_contract_id' => trim((string) ($this->validated('smaregi_contract_id') ?? '')),
            'smaregi_client_id' => trim((string) ($this->validated('smaregi_client_id') ?? '')),
            'smaregi_client_secret' => trim((string) ($this->validated('smaregi_client_secret') ?? '')),
        ];
    }
}
