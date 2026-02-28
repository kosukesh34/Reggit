<?php

namespace App\Http\Requests\Admin;

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
        return [
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')],
            'target' => ['required', Rule::in(['all', 'selected'])],
            'member_ids_input' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'クーポンを選択してください',
            'target.in' => '配布対象を選択してください',
        ];
    }
}
