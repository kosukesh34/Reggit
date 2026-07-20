<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class OnboardingStoreRequest extends FormRequest
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
            'plan' => 'required|string|in:normal,distribution,premium',
            'smaregi_contract_id' => 'required|string|max:255',
            'smaregi_client_id' => 'required|string|max:255',
            'smaregi_client_secret' => 'required|string|max:512',
            'admin_name' => 'required|string|max:255',
            'admin_login_id' => 'required|string|max:100|unique:admin_users,login_id|regex:/^[a-zA-Z0-9_-]+$/',
            'admin_email' => 'required|email|unique:admin_users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array{
     *     project_key: string,
     *     name: string,
     *     description: string|null,
     *     is_active: bool,
     *     plan: string,
     * }
     */
    public function toProjectPayload(): array
    {
        return [
            'project_key' => $this->validated('project_key'),
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'is_active' => (bool) ($this->validated('is_active') ?? true),
            'plan' => $this->validated('plan') ?? 'normal',
        ];
    }

    /**
     * @return array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     frontend_header_text: string,
     *     frontend_logo_url: string,
     *     business_hours_start: string,
     *     business_hours_end: string,
     *     line_primary_color: string,
     *     line_primary_dark: string,
     *     line_success_color: string,
     *     line_danger_color: string,
     *     smaregi_contract_id: string,
     *     smaregi_client_id: string,
     *     smaregi_client_secret: string,
     * }
     */
    public function toSettingsPayload(): array
    {
        $name = trim((string) $this->validated('name'));

        return [
            'line_liff_id' => trim((string) config('line.liff_id', '')),
            'line_liff_url' => trim((string) config('line.liff_url', '')),
            'frontend_header_text' => $name,
            'frontend_logo_url' => '',
            'business_hours_start' => '10:00',
            'business_hours_end' => '22:00',
            'line_primary_color' => '#000000',
            'line_primary_dark' => '#333333',
            'line_success_color' => '#000000',
            'line_danger_color' => '#dc3545',
            'smaregi_contract_id' => trim((string) $this->validated('smaregi_contract_id')),
            'smaregi_client_id' => trim((string) $this->validated('smaregi_client_id')),
            'smaregi_client_secret' => trim((string) $this->validated('smaregi_client_secret')),
        ];
    }

    /**
     * @return array{
     *     login_id: string,
     *     email: string,
     *     password: string,
     *     name: string,
     * }
     */
    public function toAdminPayload(): array
    {
        return [
            'login_id' => $this->validated('admin_login_id'),
            'email' => $this->validated('admin_email'),
            'password' => $this->validated('admin_password'),
            'name' => $this->validated('admin_name'),
        ];
    }
}
