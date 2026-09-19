<?php

namespace App\Http\Requests\Admin;

use App\Support\MemberCardDesignMap;
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
            'use_stamps' => 'nullable|boolean',
            'use_transaction_stamps' => 'nullable|boolean',
            'use_points' => 'nullable|boolean',
            'point_earn_rate' => 'required|integer|min:1|max:99999',
            'points_per_order' => 'nullable|integer|min:0|max:100000',
            'points_valid_days' => 'required|integer|min:1|max:3650',
            'stamp_earn_per_amount' => 'nullable|integer|min:0|max:10000000',
            'member_code_type' => 'nullable|in:sequential,random',
            'member_code_format' => 'nullable|in:numeric,alphanumeric',
            'member_code_length' => 'nullable|integer|min:1|max:20',
            'member_code_prefix' => 'nullable|string|max:10|regex:/^[A-Za-z0-9]*$/',
            'show_points_display' => 'nullable|boolean',
            'show_rank_progress' => 'nullable|boolean',
            'show_member_card_for_guest' => 'nullable|boolean',
            'member_show_rank_progress' => 'nullable|boolean',
            'member_show_point_history' => 'nullable|boolean',
            'member_show_coupon_activity' => 'nullable|boolean',
            'member_card_design' => 'nullable|string|in:'.implode(',', MemberCardDesignMap::DESIGNS),
            'member_card_display_type' => 'nullable|string|in:card,sheet',
            'member_card_code_type' => 'nullable|string|in:barcode,qrcode',
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

    /** @return array{rank_basis: string, rank_aggregation_type: string, point_earn_rate: int, points_per_order?: int, points_valid_days: int, stamp_earn_per_amount?: int, member_code_type?: string, ...} */
    public function toPayload(): array
    {
        $v = $this->validated();
        $v['show_points_display'] = $this->boolean('show_points_display', true);
        $v['show_rank_progress'] = $this->boolean('show_rank_progress', true);
        $v['show_member_card_for_guest'] = $this->boolean('show_member_card_for_guest', true);
        foreach (['member_show_rank_progress', 'member_show_point_history', 'member_show_coupon_activity'] as $settingKey) {
            if ($this->has($settingKey)) {
                $v[$settingKey] = $this->boolean($settingKey);
            }
        }
        $v['use_stamps'] = $this->boolean('use_stamps', true);
        $v['use_transaction_stamps'] = $this->boolean('use_transaction_stamps', true);
        $v['use_points'] = $this->boolean('use_points', true);
        $v['is_rank_down_enabled'] = $this->boolean('is_rank_down_enabled', false);
        $v['rank_aggregation_type'] = (string) ($v['rank_aggregation_type'] ?? 'total');
        $v['rank_period_days'] = (int) ($v['rank_period_days'] ?? 365);
        if ($v['rank_period_days'] < 1) {
            $v['rank_period_days'] = 365;
        }
        $v['points_per_order'] = (int) ($v['points_per_order'] ?? 0);
        $v['stamp_earn_per_amount'] = (int) ($v['stamp_earn_per_amount'] ?? 0);

        return $v;
    }
}
