<?php

namespace App\Http\Requests\Admin;

use App\Models\WeatherCouponRule;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 閾値は初期値を持たず必須。営業時間との整合は保存時に確かめる */
class WeatherCouponRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer'],
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->where('project_id', AdminContext::requireCurrentProjectId())],
            'min_precipitation_probability' => ['required', 'integer', 'between:10,100'],
            'min_rain_hours' => ['required', 'integer', 'between:1,24'],
            'check_hours_before_open' => ['required', 'integer', 'between:0,3'],
            'cutoff_hours_before_close' => ['required', 'integer', 'between:0,23'],
            'weekly_limit' => ['required', 'integer', 'between:1,7'],
            'target' => ['required', Rule::in([WeatherCouponRule::TARGET_STORE_MEMBERS, WeatherCouponRule::TARGET_ALL_MEMBERS])],
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => '店舗',
            'coupon_id' => 'クーポン',
            'min_precipitation_probability' => '降水確率',
            'min_rain_hours' => '雨が続く時間',
            'check_hours_before_open' => '判定する時刻',
            'cutoff_hours_before_close' => '配る締め切り',
            'weekly_limit' => '週の上限',
            'target' => '配る相手',
        ];
    }

    /** @return array{store_id: int, coupon_id: int, min_precipitation_probability: int, min_rain_hours: int, check_hours_before_open: int, cutoff_hours_before_close: int, weekly_limit: int, target: string, requires_approval: bool, is_active: bool} */
    public function rule(): array
    {
        return [
            ...array_map('intval', $this->safe()->except('target')),
            'target' => (string) $this->validated('target'),
            'requires_approval' => $this->boolean('requires_approval'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
