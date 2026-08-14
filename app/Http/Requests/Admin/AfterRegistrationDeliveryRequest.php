<?php

namespace App\Http\Requests\Admin;

use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AfterRegistrationDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'schedule_type' => ['required', 'string', 'in:'.implode(',', ScheduledDeliveryType::keys())],
            'content_type' => ['required', 'string', 'in:'.implode(',', ScheduledDeliveryContentType::keys())],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'coupon_id' => ['nullable', 'integer', 'min:1'],
            'send_notification' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'push_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'push_minute' => ['nullable', 'integer', 'min:0', 'max:59'],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:1260'],
            'delay_days' => ['nullable', 'integer', 'min:1', 'max:100'],
            'before_days' => ['nullable', 'integer', 'min:1', 'max:100'],
            'visit_times' => ['nullable', 'integer', 'min:1', 'max:100'],
            'visit_after_days' => ['nullable', 'integer', 'min:0', 'max:100'],
            'reach_rank' => ['nullable', 'string', 'max:64'],
            'total_points' => ['nullable', 'integer', 'min:1'],
            'total_stamps' => ['nullable', 'integer', 'min:1'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable'],
            'last_month_visit_min' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('schedule_type');
            $content = (string) $this->input('content_type');
            if ($content === ScheduledDeliveryContentType::COUPON_ONLY && (int) $this->input('coupon_id') <= 0) {
                $validator->errors()->add('coupon_id', 'クーポンのみ配信ではクーポンを選択してください');
            }
            if ($content !== ScheduledDeliveryContentType::COUPON_ONLY && trim((string) $this->input('title')) === '') {
                $validator->errors()->add('title', 'お知らせタイトルを入力してください');
            }
            if ($type === ScheduledDeliveryType::RANK_UP_ARRIVAL && trim((string) $this->input('reach_rank')) === '') {
                $validator->errors()->add('reach_rank', '到達ランクを選択してください');
            }
            $dayOfMonth = $this->input('day_of_month');
            if ($dayOfMonth !== null && $dayOfMonth !== '' && $dayOfMonth !== 'last_day') {
                $day = (int) $dayOfMonth;
                if ($day < 1 || $day > 31) {
                    $validator->errors()->add('day_of_month', '日付は1〜31または月末を指定してください');
                }
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'schedule_type.required' => '配信タイプを選択してください',
            'content_type.required' => '配信内容を選択してください',
        ];
    }
}
