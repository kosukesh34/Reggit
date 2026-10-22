<?php

namespace App\Http\Requests\Admin;

use App\Models\Store;
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
            'custom_shop_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i',
                Rule::unique('projects', 'custom_shop_domain')->ignore($projectId),
            ],
            'is_active' => 'nullable|boolean',
            'service_status' => 'sometimes|required|in:active,unpaid,cancelled',
            'plan' => 'required|string|in:normal,distribution,premium',
            'max_active_stores' => ['sometimes', 'integer', 'min:1', 'max:1000', function (string $attribute, mixed $value, \Closure $fail) use ($projectId): void {
                $active = Store::where('project_id', $projectId)->where('is_active', true)->count();
                if ((int) $value < $active) {
                    $fail("現在有効な店舗が{$active}件あります。先に利用店舗を減らしてください。");
                }
            }],
            'smaregi_contract_id' => 'nullable|string|max:255',
            'smaregi_client_id' => 'nullable|string|max:255',
            'smaregi_client_secret' => 'nullable|string|max:512',
        ];
    }

    public function toProjectPayload(): array
    {
        $domain = strtolower(trim((string) ($this->validated('custom_shop_domain') ?? '')));

        return [
            'project_key' => $this->validated('project_key'),
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'custom_shop_domain' => $domain !== '' ? $domain : null,
            'is_active' => (bool) ($this->validated('is_active') ?? false),
            'plan' => $this->validated('plan') ?? 'normal',
            ...($this->has('max_active_stores') ? ['max_active_stores' => (int) $this->validated('max_active_stores')] : []),
            ...($this->has('service_status') ? ['service_status' => $this->validated('service_status')] : []),
        ];
    }

    public function toProjectSettingsPayload(): array
    {
        $payload = [
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
