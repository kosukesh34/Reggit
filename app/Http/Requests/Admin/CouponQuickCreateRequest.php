<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponQuickCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = auth('admin')->user()?->project_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->where(fn ($q) => $projectId ? $q->where('project_id', $projectId) : $q),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|string|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'use_display' => 'nullable|string|in:none,barcode,qr,both',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'クーポンコード',
            'name' => 'クーポン名',
            'description' => '説明',
            'type' => '割引種別',
            'value' => '割引値',
            'min_order_amount' => '最低注文金額',
            'start_at' => '開始日',
            'end_at' => '終了日',
            'use_display' => '使用時の表示',
        ];
    }
}
