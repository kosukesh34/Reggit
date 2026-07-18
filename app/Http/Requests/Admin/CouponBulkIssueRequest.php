<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponDeliveryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponBulkIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deliveryType = (string) $this->input('delivery_type', CouponDeliveryType::IMMEDIATE_ALL);

        return [
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')],
            'delivery_type' => ['required', Rule::in(array_keys(CouponDeliveryType::getValues()))],
            'target_member_ids' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::personalValues(), true)),
                'nullable',
                'array',
                'min:1',
            ],
            'target_member_ids.*' => ['integer', 'min:1'],
            'member_ids_input' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::scheduledValues(), true)),
                'nullable',
                'date',
                'after:now',
            ],
            'segment_period_days' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::segmentValues(), true)),
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'segment_condition' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::segmentValues(), true)),
                'nullable',
                Rule::in(['min_amount', 'no_orders']),
            ],
            'segment_min_amount' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::segmentValues(), true) && $this->input('segment_condition') === 'min_amount'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'new_member_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'segment_channel' => ['nullable', Rule::in(['line', 'app', 'both'])],
            'segment_birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'segment_visit_count_min' => ['nullable', 'integer', 'min:0'],
            'segment_visit_count_max' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_min' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_max' => ['nullable', 'integer', 'min:0'],
            'segment_last_visit_mode' => ['nullable', Rule::in(['inactive_days', 'visited_within_days'])],
            'segment_last_visit_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'segment_sex' => ['nullable', Rule::in(['1', '2'])],
            'segment_rank' => ['nullable', 'string', 'max:64'],
            'segment_product_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'クーポンを選択してください',
            'delivery_type.in' => '配信種別を選択してください',
            'target_member_ids.required' => '個人指定では配信対象の会員を1人以上選択してください',
            'target_member_ids.min' => '個人指定では配信対象の会員を1人以上選択してください',
            'scheduled_at.required' => '予約配布では配布日時を指定してください',
            'segment_period_days.required' => '売上条件では対象期間を選択してください',
            'segment_condition.required' => '売上条件では条件を選択してください',
            'segment_min_amount.required' => '購入額条件では最低金額を入力してください',
        ];
    }
}
