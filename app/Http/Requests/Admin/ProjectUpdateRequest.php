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
            'line_plan_enabled' => 'nullable|boolean',
            'max_active_stores' => ['sometimes', 'integer', 'min:1', 'max:1000', function (string $attribute, mixed $value, \Closure $fail) use ($projectId): void {
                $active = Store::where('project_id', $projectId)->where('is_active', true)->count();
                if ((int) $value < $active) {
                    $fail("現在有効な店舗が{$active}件あります。先に利用店舗を減らしてください。");
                }
            }],
            // 契約項目だけを送る呼び出しでは LINE 設定を変えない
            'line_liff_id' => 'sometimes|required|string|max:128',
            'line_liff_url' => 'sometimes|required|url|max:2048',
            'line_friend_add_url' => 'nullable|url|max:2048',
            'line_channel_access_token' => 'nullable|string|max:1024',
            'line_channel_secret' => 'nullable|string|max:255',
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
            'line_plan_enabled' => $this->boolean('line_plan_enabled'),
            ...($this->has('max_active_stores') ? ['max_active_stores' => (int) $this->validated('max_active_stores')] : []),
            ...($this->has('service_status') ? ['service_status' => $this->validated('service_status')] : []),
        ];
    }

    /**
     * @return array{
     *     line_liff_id: string,
     *     line_liff_url: string,
     *     line_friend_add_url: string|null,
     *     line_channel_access_token: string|null,
     *     line_channel_secret: string|null,
     * }|null
     */
    public function toLineConnectionPayload(): ?array
    {
        if (! $this->has('line_liff_id')) {
            return null;
        }

        return [
            'line_liff_id' => trim((string) $this->validated('line_liff_id')),
            'line_liff_url' => trim((string) $this->validated('line_liff_url')),
            'line_friend_add_url' => $this->validated('line_friend_add_url'),
            'line_channel_access_token' => $this->validated('line_channel_access_token'),
            'line_channel_secret' => $this->validated('line_channel_secret'),
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
