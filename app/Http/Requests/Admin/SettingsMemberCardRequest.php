<?php

namespace App\Http\Requests\Admin;

use App\Support\MemberCardDesignMap;
use App\UseCases\Admin\Settings\Data\UpdateMemberCardInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsMemberCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'show_points_display' => 'nullable|boolean',
            'show_rank_progress' => 'nullable|boolean',
            'show_member_card_for_guest' => 'nullable|boolean',
            'member_show_rank_progress' => 'nullable|boolean',
            'member_show_point_history' => 'nullable|boolean',
            'member_show_coupon_activity' => 'nullable|boolean',
            'member_card_design' => 'nullable|string|in:'.implode(',', MemberCardDesignMap::DESIGNS),
            'member_card_display_type' => 'nullable|in:card,sheet',
            'member_card_code_type' => 'nullable|in:barcode,qrcode',
        ];
    }

    public function toInput(int $projectId): UpdateMemberCardInput
    {
        $v = $this->validated();

        return new UpdateMemberCardInput(
            projectId: $projectId,
            showPoints: array_key_exists('show_points_display', $v) ? (bool) $v['show_points_display'] : null,
            showRankProgress: array_key_exists('show_rank_progress', $v) ? (bool) $v['show_rank_progress'] : null,
            showForGuest: array_key_exists('show_member_card_for_guest', $v) ? (bool) $v['show_member_card_for_guest'] : null,
            showScreenRankProgress: array_key_exists('member_show_rank_progress', $v) ? (bool) $v['member_show_rank_progress'] : null,
            showPointHistory: array_key_exists('member_show_point_history', $v) ? (bool) $v['member_show_point_history'] : null,
            showCouponActivity: array_key_exists('member_show_coupon_activity', $v) ? (bool) $v['member_show_coupon_activity'] : null,
            design: $v['member_card_design'] ?? null,
            displayType: $v['member_card_display_type'] ?? null,
            codeType: $v['member_card_code_type'] ?? null,
        );
    }
}
