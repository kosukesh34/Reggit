<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateAnnouncementsInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsAnnouncementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'announcement_default_pinned' => 'nullable|boolean',
            'announcement_default_expiry_days' => 'nullable|integer|min:0|max:365',
        ];
    }

    public function toInput(int $projectId): UpdateAnnouncementsInput
    {
        $v = $this->validated();

        return new UpdateAnnouncementsInput($projectId, (bool) ($v['announcement_default_pinned'] ?? false), (int) ($v['announcement_default_expiry_days'] ?? 0));
    }
}
