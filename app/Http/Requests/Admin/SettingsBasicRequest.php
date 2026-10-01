<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateBasicSettingsInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsBasicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_hours_start' => 'required|date_format:H:i',
            'business_hours_end' => 'required|date_format:H:i',
            'order_mode' => 'required|string|in:both,mobile_only,in_store_only',
            'order_line_notify_enabled' => 'nullable|in:0,1',
            'order_ready_line_notify_enabled' => 'nullable|in:0,1',
        ];
    }

    public function toInput(int $projectId): UpdateBasicSettingsInput
    {
        $v = $this->validated();

        return new UpdateBasicSettingsInput(
            $projectId, $v['business_hours_start'], $v['business_hours_end'], $v['order_mode'],
            (bool) ($v['order_line_notify_enabled'] ?? false), (bool) ($v['order_ready_line_notify_enabled'] ?? false),
        );
    }
}
