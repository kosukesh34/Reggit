<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateRankSettingsInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRanksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rank_basis' => 'required|in:points,stamps,max',
            'rank_aggregation_type' => 'required|in:total,period',
            'rank_period_days' => 'nullable|integer|min:1|max:3650',
            'is_rank_down_enabled' => 'nullable|boolean',
            'point_earn_rate' => 'required|integer|min:1|max:99999',
            'points_per_order' => 'nullable|integer|min:0|max:100000',
            'points_valid_days' => 'required|integer|min:1|max:3650',
            'stamp_earn_per_amount' => 'nullable|integer|min:0|max:10000000',
            'ranks' => 'nullable|array',
            'ranks.*.key' => 'nullable|string|max:50',
            'ranks.*.label' => 'nullable|string|max:50',
            'ranks.*.min_points' => 'nullable|integer|min:0|max:10000000',
            'ranks.*.order' => 'nullable|integer|min:0|max:999',
            'ranks.*.is_active' => 'nullable',
            'ranks.*.card_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
            'ranks.*.point_earn_rate' => 'nullable|integer|min:1|max:99999',
            'ranks.*.stamp_earn_per_amount' => 'nullable|integer|min:0|max:10000000',
        ];
    }

    public function toInput(int $projectId): UpdateRankSettingsInput
    {
        $v = $this->validated();

        return new UpdateRankSettingsInput(
            projectId: $projectId,
            rankBasis: (string) $v['rank_basis'],
            aggregationType: (string) ($v['rank_aggregation_type'] ?? 'total'),
            periodDays: (int) ($v['rank_period_days'] ?? 365),
            rankDownEnabled: (bool) ($v['is_rank_down_enabled'] ?? false),
            pointEarnRate: (int) $v['point_earn_rate'],
            pointsValidDays: (int) $v['points_valid_days'],
            pointsPerOrder: (int) ($v['points_per_order'] ?? 0),
            stampEarnPerAmount: (int) ($v['stamp_earn_per_amount'] ?? 0),
            ranks: (array) ($v['ranks'] ?? []),
        );
    }
}
