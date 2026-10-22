<?php

namespace App\Http\Requests\Admin;

use App\Domain\Settings\ShopSettingKeys;
use App\UseCases\Admin\Settings\Data\UpdateFeatureSettingsInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'features' => 'nullable|array',
            'features.*' => 'boolean',
            'use_points' => 'nullable|boolean',
            'point_usage_scope' => 'nullable|in:app,store,both',
            'use_transaction_stamps' => 'nullable|boolean',
        ];
    }

    public function toInput(int $projectId): UpdateFeatureSettingsInput
    {
        $v = $this->validated();

        return new UpdateFeatureSettingsInput(
            projectId: $projectId,
            usePoints: array_key_exists('use_points', $v) ? (bool) $v['use_points'] : null,
            pointUsageScope: isset($v['point_usage_scope']) ? (string) $v['point_usage_scope'] : null,
            useTransactionStamps: array_key_exists('use_transaction_stamps', $v) ? (bool) $v['use_transaction_stamps'] : null,
            features: array_map(fn ($value) => (bool) $value, array_intersect_key($v['features'] ?? [], ShopSettingKeys::FEATURE_FLAG_LABELS)),
        );
    }
}
