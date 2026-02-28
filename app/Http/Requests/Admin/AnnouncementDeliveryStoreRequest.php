<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'is_pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'delivery_type.in' => '配信種別を選択してください',
            'target_member_ids.required' => '個人宛配信では配信対象の会員を選択してください',
            'target_member_ids.min' => '個人宛配信では配信対象の会員を1人以上選択してください',
            'scheduled_at.required' => '予約配信では配信日時を指定してください',
        ];
    }
}
