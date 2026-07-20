<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    private const HEX_REGEX = '/^#[0-9a-fA-F]{6}$/';

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
            'plan' => 'required|string|in:normal,distribution,premium',
            'line_liff_id' => 'required|string|max:128',
            'line_liff_url' => 'required|url|max:2048',
            'frontend_header_text' => 'required|string|max:255',
            'frontend_logo_url' => 'nullable|url|max:2048',
            'business_hours_start' => 'required|date_format:H:i',
            'business_hours_end' => 'required|date_format:H:i|after:business_hours_start',
            'line_primary_color' => ['nullable', 'string', 'regex:'.self::HEX_REGEX],
            'line_primary_dark' => ['nullable', 'string', 'regex:'.self::HEX_REGEX],
            'line_success_color' => ['nullable', 'string', 'regex:'.self::HEX_REGEX],
            'line_danger_color' => ['nullable', 'string', 'regex:'.self::HEX_REGEX],
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
            'is_active' => (bool) ($this->validated('is_active') ?? false),
            'plan' => $this->validated('plan') ?? 'normal',
        ];
    }

    public function toProjectSettingsPayload(): array
    {
        $payload = [
            'line_liff_id' => trim((string) $this->validated('line_liff_id')),
            'line_liff_url' => trim((string) $this->validated('line_liff_url')),
            'frontend_header_text' => trim((string) $this->validated('frontend_header_text')),
            'frontend_logo_url' => trim((string) ($this->validated('frontend_logo_url') ?? '')),
            'business_hours_start' => $this->validated('business_hours_start'),
            'business_hours_end' => $this->validated('business_hours_end'),
            'line_primary_color' => $this->validated('line_primary_color') ?? '#000000',
            'line_primary_dark' => $this->validated('line_primary_dark') ?? '#333333',
            'line_success_color' => $this->validated('line_success_color') ?? '#000000',
            'line_danger_color' => $this->validated('line_danger_color') ?? '#dc3545',
            'smaregi_contract_id' => trim((string) ($this->validated('smaregi_contract_id') ?? '')),
            'smaregi_client_id' => trim((string) ($this->validated('smaregi_client_id') ?? '')),
        ];

        $secret = trim((string) ($this->validated('smaregi_client_secret') ?? ''));
        if ($secret !== '') {
            $payload['smaregi_client_secret'] = $secret;
        }

        return $payload;
    }
}
