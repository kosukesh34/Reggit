<?php

namespace App\Http\Requests\Admin;

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
            'point_earn_rate' => 'required|integer|min:1|max:100000',
            'points_valid_days' => 'required|integer|min:1|max:3650',
            'member_code_type' => 'nullable|in:sequential,random',
            'member_code_format' => 'nullable|in:numeric,alphanumeric',
            'member_code_length' => 'nullable|integer|min:1|max:20',
            'member_code_prefix' => 'nullable|string|max:10|regex:/^[A-Za-z0-9]*$/',
            'show_points_display' => 'nullable|boolean',
            'show_rank_progress' => 'nullable|boolean',
            'show_member_card_for_guest' => 'nullable|boolean',
            'member_card_design' => 'nullable|string|in:default,logo_top,logo_center,logo_header,compact',
            'member_card_display_type' => 'nullable|string|in:card,sheet',
            'member_card_code_type' => 'nullable|string|in:barcode,qrcode',
            'ranks' => 'array',
            'ranks.*.key' => 'nullable|string|max:50',
            'ranks.*.label' => 'nullable|string|max:50',
            'ranks.*.min_points' => 'nullable|integer|min:0|max:10000000',
            'ranks.*.order' => 'nullable|integer|min:0|max:999',
            'ranks.*.is_active' => 'nullable',
            'ranks.*.icon_url' => 'nullable|url|max:500',
            'ranks.*.stamp_background_url' => 'nullable|url|max:500',
            'ranks.*.card_color' => 'nullable|string|max:20|regex:/^#[0-9A-Fa-f]{3,6}$/',
        ];
    }

    /** @return array{rank_basis: string, point_earn_rate: int, points_valid_days: int, member_code_type?: string, member_code_format?: string, member_code_length?: int, member_code_prefix?: string, show_points_display?: bool, show_rank_progress?: bool, show_member_card_for_guest?: bool, member_card_design?: string, ranks: array<int, array{key?: string, label?: string, min_points?: int, order?: int, is_active?: bool, icon_url?: string|null, stamp_background_url?: string|null, card_color?: string|null}>} */
    public function toPayload(): array
    {
        $v = $this->validated();
        $v['show_points_display'] = $this->boolean('show_points_display', true);
        $v['show_rank_progress'] = $this->boolean('show_rank_progress', true);
        $v['show_member_card_for_guest'] = $this->boolean('show_member_card_for_guest', true);
        return $v;
    }
}
