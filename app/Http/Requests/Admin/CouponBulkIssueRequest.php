<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponDeliveryType;
use App\Enums\RfmSegment;
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

    protected function prepareForValidation(): void
    {
        if ($this->filled('target_rank') === false && $this->filled('segment_rank')) {
            $this->merge(['target_rank' => $this->input('segment_rank')]);
        }

        if ($this->filled('audience_type')) {
            $audience = (string) $this->input('audience_type', 'immediate_bulk');
            $isScheduled = (string) $this->input('delivery_timing', 'immediate') === 'scheduled';
            if ($this->boolean('event_rank_up')) {
                $this->merge(['delivery_type' => CouponDeliveryType::EVENT_RANK_UP_ARRIVAL]);

                return;
            }

            $this->merge(['delivery_type' => $this->resolveDeliveryType($audience, $isScheduled)]);

            return;
        }

        if ($this->filled('delivery_type')) {
            return;
        }
    }

    private function resolveDeliveryType(string $audience, bool $isScheduled): string
    {
        if ($audience === 'personal') {
            return $isScheduled ? CouponDeliveryType::SCHEDULED_PERSONAL : CouponDeliveryType::IMMEDIATE_PERSONAL;
        }
        if ($audience === 'segment') {
            $salesOnly = in_array((string) $this->input('segment_condition', ''), ['min_amount', 'no_orders'], true)
                && ! $this->filled('segment_channel')
                && ! $this->filled('segment_sex')
                && ! $this->filled('segment_rank')
                && ! $this->filled('segment_rfm_segments')
                && ! $this->filled('segment_tag_ids')
                && ! $this->filled('segment_birthday_months');
            if ($salesOnly) {
                return $isScheduled ? CouponDeliveryType::SCHEDULED_SEGMENT_SALES : CouponDeliveryType::IMMEDIATE_SEGMENT_SALES;
            }

            return $isScheduled ? CouponDeliveryType::SCHEDULED_SEGMENT_ATTRIBUTES : CouponDeliveryType::IMMEDIATE_SEGMENT_ATTRIBUTES;
        }

        return $isScheduled ? CouponDeliveryType::SCHEDULED_ALL : CouponDeliveryType::IMMEDIATE_ALL;
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
            'audience_type' => ['nullable', Rule::in(['immediate_bulk', 'personal', 'segment'])],
            'delivery_timing' => ['nullable', Rule::in(['immediate', 'scheduled'])],
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
            'segment_birthday_months' => ['nullable', 'array', 'max:12'],
            'segment_birthday_months.*' => ['integer', 'min:1', 'max:12', 'distinct'],
            'segment_tag_ids' => ['nullable', 'array'],
            'segment_tag_ids.*' => ['integer', 'min:1', 'distinct'],
            'event_rank_up' => ['nullable'],
            'send_notification' => ['nullable', 'boolean'],
            'segment_visit_count_min' => ['nullable', 'integer', 'min:0'],
            'segment_visit_count_max' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_min' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_max' => ['nullable', 'integer', 'min:0'],
            'segment_last_visit_mode' => ['nullable', Rule::in(['inactive_days', 'visited_within_days', 'days_after'])],
            'segment_last_visit_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'segment_sex' => ['nullable', Rule::in(['1', '2'])],
            'segment_rank' => ['nullable', 'string', 'max:64'],
            'segment_rfm_segments' => ['nullable', 'array'],
            'segment_rfm_segments.*' => ['string', Rule::in(RfmSegment::keys()), 'distinct'],
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
