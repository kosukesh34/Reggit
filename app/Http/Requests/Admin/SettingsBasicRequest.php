<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateStoreOperationSettingsInput;
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

    public function toInput(int $projectId): UpdateStoreOperationSettingsInput
    {
        return new UpdateStoreOperationSettingsInput(
            projectId: $projectId,
            businessHoursStart: (string) $this->validated('business_hours_start'),
            businessHoursEnd: (string) $this->validated('business_hours_end'),
            orderMode: (string) $this->validated('order_mode'),
            orderLineNotifyEnabled: $this->boolean('order_line_notify_enabled'),
            orderReadyLineNotifyEnabled: $this->boolean('order_ready_line_notify_enabled'),
        );
    }
}
