<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AfterRegistrationDeliveryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delay_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:5000'],
            'coupon_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'delay_minutes.required' => '配信までの分数を入力してください',
            'delay_minutes.min' => '1分以上を指定してください',
            'delay_minutes.max' => '10080分（7日）以内で指定してください',
            'title.required' => 'タイトルを入力してください',
        ];
    }
}
