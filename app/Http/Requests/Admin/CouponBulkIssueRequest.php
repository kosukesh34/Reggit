<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponDeliveryType;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CouponBulkIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deliveryType = (string) $this->input('delivery_type', CouponDeliveryType::IMMEDIATE_ALL);
        $projectId = AdminContext::getCurrentProjectId()
            ?? auth('admin')->user()?->project_id;

        $couponExists = Rule::exists('coupons', 'id');
        if ($projectId !== null) {
            $couponExists = $couponExists->where('project_id', (int) $projectId);
        }

        return [
            'coupon_id' => ['required', 'integer', $couponExists],
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
            'target_rank' => [
                Rule::requiredIf(
                    in_array($deliveryType, CouponDeliveryType::rankValues(), true)
                    || CouponDeliveryType::isEventRankUpArrival($deliveryType)
                ),
                'nullable',
                'string',
                'max:64',
            ],
            'stamp_card_id' => ['nullable', 'integer', 'min:1'],
            'stamp_count_min' => ['nullable', 'integer', 'min:0'],
            'stamp_completed' => ['nullable'],
            'days_after_last_visit' => [
                Rule::requiredIf(in_array($deliveryType, CouponDeliveryType::daysAfterVisitValues(), true)),
                'nullable',
                'integer',
                'min:1',
                'max:730',
            ],
            'segment_channel' => ['nullable', Rule::in(['line', 'app', 'both'])],
            'segment_birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'segment_visit_count_min' => ['nullable', 'integer', 'min:0'],
            'segment_visit_count_max' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_min' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_max' => ['nullable', 'integer', 'min:0'],
            'segment_last_visit_mode' => ['nullable', Rule::in(['inactive_days', 'visited_within_days', 'days_after'])],
            'segment_last_visit_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'segment_sex' => ['nullable', Rule::in(['1', '2'])],
            'segment_rank' => ['nullable', 'string', 'max:64'],
            'segment_product_id' => ['nullable', 'integer', 'min:1'],
            'segment_stamp_card_id' => ['nullable', 'integer', 'min:1'],
            'segment_stamp_count_min' => ['nullable', 'integer', 'min:0'],
            'segment_stamp_completed' => ['nullable'],
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
            'target_rank.required' => 'ランク配信では対象ランクを選択してください',
            'days_after_last_visit.required' => '最終来店から何日後かを入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $deliveryType = (string) $this->input('delivery_type', '');
            if (in_array($deliveryType, CouponDeliveryType::stampValues(), true) === false) {
                return;
            }

            $hasCard = $this->filled('stamp_card_id') || $this->filled('segment_stamp_card_id');
            $hasMin = $this->filled('stamp_count_min') || $this->filled('segment_stamp_count_min');
            $isCompleted = $this->boolean('stamp_completed') || $this->boolean('segment_stamp_completed');
            if ($hasCard === false && $hasMin === false && $isCompleted === false) {
                $validator->errors()->add(
                    'stamp_count_min',
                    'スタンプ配信ではカード・累計枚数・達成のいずれかを指定してください'
                );
            }
        });
    }
}
