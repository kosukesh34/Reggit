<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use App\Services\Admin\MemberSegmentService;
use App\Support\MemberChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnnouncementDeliveryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deliveryType = (string) $this->input('delivery_type');

        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'delivery_type' => ['required', Rule::in([
                Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                Announcement::DELIVERY_TYPE_PERSONAL,
                Announcement::DELIVERY_TYPE_SCHEDULED,
                Announcement::DELIVERY_TYPE_SEGMENT,
            ])],
            'target_member_ids' => [
                Rule::requiredIf($deliveryType === Announcement::DELIVERY_TYPE_PERSONAL),
                'nullable',
                'array',
                'min:1',
            ],
            'target_member_ids.*' => ['integer', 'min:1'],
            'scheduled_at' => [
                Rule::requiredIf($deliveryType === Announcement::DELIVERY_TYPE_SCHEDULED),
                'nullable',
                'date',
                'after:now',
            ],
            'segment_channel' => ['nullable', Rule::in([
                MemberChannel::LINE,
                MemberChannel::APP,
                MemberChannel::BOTH,
            ])],
            'segment_birthday_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'segment_visit_count_min' => ['nullable', 'integer', 'min:0'],
            'segment_visit_count_max' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_min' => ['nullable', 'integer', 'min:0'],
            'segment_lifetime_points_max' => ['nullable', 'integer', 'min:0'],
            'segment_last_visit_mode' => ['nullable', Rule::in([
                MemberSegmentService::LAST_VISIT_INACTIVE,
                MemberSegmentService::LAST_VISIT_WITHIN,
            ])],
            'segment_last_visit_days' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
                Rule::requiredIf(fn () => filled($this->input('segment_last_visit_mode'))),
            ],
            'segment_sex' => ['nullable', Rule::in(['1', '2'])],
            'segment_rank' => ['nullable', 'string', 'max:64'],
            'segment_period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'segment_condition' => ['nullable', Rule::in(['min_amount', 'no_orders'])],
            'segment_min_amount' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(
                    $deliveryType === Announcement::DELIVERY_TYPE_SEGMENT
                    && $this->input('segment_condition') === 'min_amount'
                ),
            ],
            'delivery_coupon_id' => ['nullable', 'integer', 'min:1'],
            'is_pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('delivery_type') !== Announcement::DELIVERY_TYPE_SEGMENT) {
                return;
            }

            $criteria = app(MemberSegmentService::class)->buildCriteriaFromInput($this->all());
            if ($criteria === null) {
                $validator->errors()->add('segment_channel', 'セグメント配信では1つ以上の絞り込み条件を指定してください');
            }

            $min = $this->input('segment_visit_count_min');
            $max = $this->input('segment_visit_count_max');
            if ($min !== null && $min !== '' && $max !== null && $max !== '' && (int) $min > (int) $max) {
                $validator->errors()->add('segment_visit_count_max', '来店回数の上限は下限以上にしてください');
            }

            $pointsMin = $this->input('segment_lifetime_points_min');
            $pointsMax = $this->input('segment_lifetime_points_max');
            if ($pointsMin !== null && $pointsMin !== '' && $pointsMax !== null && $pointsMax !== ''
                && (int) $pointsMin > (int) $pointsMax) {
                $validator->errors()->add('segment_lifetime_points_max', '累計付与ポイントの上限は下限以上にしてください');
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'delivery_type.in' => '配信種別を選択してください',
            'target_member_ids.required' => '個人宛配信では配信対象の会員を選択してください',
            'target_member_ids.min' => '個人宛配信では配信対象の会員を1人以上選択してください',
            'scheduled_at.required' => '予約配信では配信日時を指定してください',
            'segment_min_amount.required' => '購入額以上の場合は最低金額を入力してください',
            'segment_last_visit_days.required' => '最終来店の条件を選んだ場合は日数を指定してください',
        ];
    }
}
