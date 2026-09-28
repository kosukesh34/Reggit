<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementAudienceResolveMode;
use App\Enums\RfmSegment;
use App\Models\Announcement;
use App\Services\Admin\MemberSegmentService;
use App\Support\MemberChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnnouncementDeliveryStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $legacyType = (string) $this->input('delivery_type', '');
        $audienceType = (string) $this->input('audience_type', '');
        $deliveryTiming = (string) $this->input('delivery_timing', '');

        if ($audienceType === '') {
            $audienceType = $legacyType === Announcement::DELIVERY_TYPE_SCHEDULED
                ? Announcement::DELIVERY_TYPE_IMMEDIATE_BULK
                : $legacyType;
        }
        if ($deliveryTiming === '') {
            $deliveryTiming = $legacyType === Announcement::DELIVERY_TYPE_SCHEDULED
                ? Announcement::DELIVERY_TIMING_SCHEDULED
                : Announcement::DELIVERY_TIMING_IMMEDIATE;
        }

        $this->merge([
            'audience_type' => $audienceType,
            'delivery_timing' => $deliveryTiming,
            'audience_resolve_mode' => $this->input('audience_resolve_mode') === AnnouncementAudienceResolveMode::AT_SEND
                ? AnnouncementAudienceResolveMode::AT_SEND
                : AnnouncementAudienceResolveMode::SNAPSHOT,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $audienceType = (string) $this->input('audience_type');
        $deliveryTiming = (string) $this->input('delivery_timing');

        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'audience_type' => ['required', Rule::in([
                Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                Announcement::DELIVERY_TYPE_PERSONAL,
                Announcement::DELIVERY_TYPE_SEGMENT,
            ])],
            'delivery_timing' => ['required', Rule::in([
                Announcement::DELIVERY_TIMING_IMMEDIATE,
                Announcement::DELIVERY_TIMING_SCHEDULED,
            ])],
            'audience_resolve_mode' => ['nullable', Rule::in(AnnouncementAudienceResolveMode::keys())],
            'delivery_type' => ['nullable', 'string'],
            'target_member_ids' => [
                Rule::requiredIf($audienceType === Announcement::DELIVERY_TYPE_PERSONAL),
                'nullable',
                'array',
                'min:1',
            ],
            'target_member_ids.*' => ['integer', 'min:1'],
            'scheduled_at' => [
                Rule::requiredIf($deliveryTiming === Announcement::DELIVERY_TIMING_SCHEDULED),
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
            'segment_birthday_months' => ['nullable', 'array', 'max:12'],
            'segment_birthday_months.*' => ['integer', 'min:1', 'max:12', 'distinct'],
            'segment_tag_ids' => ['nullable', 'array'],
            'segment_tag_ids.*' => ['integer', 'min:1', 'distinct'],
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
            'segment_rfm_segments' => ['nullable', 'array'],
            'segment_rfm_segments.*' => ['string', Rule::in(RfmSegment::keys()), 'distinct'],
            'segment_product_id' => ['nullable', 'integer', 'min:1'],
            'segment_operators' => ['nullable', 'array'],
            'segment_operators.*' => ['nullable', Rule::in(['and', 'or'])],
            'segment_profile' => ['nullable', 'array'],
            'segment_profile.*' => ['nullable'],
            'segment_period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'segment_condition' => ['nullable', Rule::in(['min_amount', 'no_orders'])],
            'segment_min_amount' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(
                    $audienceType === Announcement::DELIVERY_TYPE_SEGMENT
                    && $this->input('segment_condition') === 'min_amount'
                ),
            ],
            'delivery_coupon_id' => ['nullable', 'integer', 'min:1'],
            'delivery_coupon_ids' => ['nullable', 'array', 'max:20'],
            'delivery_coupon_ids.*' => ['integer', 'min:1', 'distinct'],
            'is_pinned' => ['nullable', 'boolean'],
            'send_notification' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('audience_type') !== Announcement::DELIVERY_TYPE_SEGMENT) {
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
            'audience_type.in' => '配信対象を選択してください',
            'delivery_timing.in' => '配信タイミングを選択してください',
            'target_member_ids.required' => '個人宛配信では配信対象の会員を選択してください',
            'target_member_ids.min' => '個人宛配信では配信対象の会員を1人以上選択してください',
            'scheduled_at.required' => '予約配信では配信日時を指定してください',
            'segment_min_amount.required' => '購入額以上の場合は最低金額を入力してください',
            'segment_last_visit_days.required' => '最終来店の条件を選んだ場合は日数を指定してください',
        ];
    }
}
