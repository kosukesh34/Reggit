<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'device_once_enabled' => $this->boolean('device_once_enabled'),
        ]);
        foreach (['min_order_amount', 'max_order_amount', 'usage_limit'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
        if ($this->input('apply_condition_division') === null || $this->input('apply_condition_division') === '') {
            $this->merge(['apply_condition_division' => CouponApplyConditionDivision::ALL]);
        }
        if ($this->input('combine_division') === null || $this->input('combine_division') === '') {
            $this->merge(['combine_division' => CouponCombineDivision::ALLOWED]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:64',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|string|url|max:500',
            'image' => 'nullable|file|image|max:2048',
            'image_fit' => 'nullable|string|in:cover,contain',
            'use_display' => 'nullable|string|in:none,barcode,qr,both',
            'type' => 'required|string|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0',
            'apply_condition_division' => 'nullable|string|in:'.implode(',', CouponApplyConditionDivision::keys()),
            'combine_division' => 'nullable|string|in:'.implode(',', CouponCombineDivision::keys()),
            'usage_limit' => 'nullable|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_active' => 'nullable|boolean',
            'device_once_enabled' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('min_order_amount');
            $max = $this->input('max_order_amount');
            if ($min === null || $min === '' || $max === null || $max === '') {
                return;
            }
            if ((float) $max < (float) $min) {
                $validator->errors()->add('max_order_amount', '最高対象金額は最低対象金額以上にしてください');
            }
        });
    }
}
