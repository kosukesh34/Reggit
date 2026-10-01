<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\MemberCodeSettingsInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsMemberCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code_type' => 'required|in:sequential,random',
            'member_code_format' => 'required|in:numeric,alphanumeric',
            'member_code_length' => 'required|integer|min:1|max:20',
            'member_code_prefix' => 'nullable|string|max:10|regex:/^[A-Za-z0-9]*$/',
        ];
    }

    public function toInput(int $projectId): MemberCodeSettingsInput
    {
        $v = $this->validated();

        return new MemberCodeSettingsInput(
            projectId: $projectId,
            type: $v['member_code_type'],
            format: $v['member_code_format'],
            length: (int) $v['member_code_length'],
            prefix: (string) ($v['member_code_prefix'] ?? ''),
        );
    }
}
