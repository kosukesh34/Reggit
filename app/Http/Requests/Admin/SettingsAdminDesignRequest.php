<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateAdminDesignInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsAdminDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_sidebar_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_header_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function toInput(int $projectId): UpdateAdminDesignInput
    {
        $v = $this->validated();

        return new UpdateAdminDesignInput(
            projectId: $projectId,
            sidebarColor: (string) ($v['admin_sidebar_color'] ?? ''),
            headerColor: (string) ($v['admin_header_color'] ?? ''),
            primaryColor: (string) ($v['admin_primary_color'] ?? ''),
        );
    }
}
