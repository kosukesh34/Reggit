<?php

namespace App\Http\Requests\Admin;

use App\Domain\Coupon\ValueObjects\CouponUsageWindow;
use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        // 何も選ばない・追加しないときは制限なし（null）として保存する
        $days = array_values(array_filter((array) $this->input('usable_days', []), fn ($day) => $day !== null && $day !== ''));
        $ranges = array_values(array_filter((array) $this->input('usable_time_ranges', []), fn ($range) => is_array($range)));
        $this->merge([
            'usable_days' => $days === [] ? null : $days,
            'usable_time_ranges' => $ranges === [] ? null : $ranges,
        ]);
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
            'target_product_ids' => 'required_if:apply_condition_division,'.CouponApplyConditionDivision::INCLUDE_TARGET_PRODUCT.'|array',
            'target_product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('project_id', AdminContext::requireCurrentProjectId())],
            'usage_limit' => 'nullable|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'usable_days' => 'nullable|array',
            'usable_days.*' => 'distinct|in:0,1,2,3,4,5,6,'.CouponUsageWindow::HOLIDAY,
            'usable_time_ranges' => 'nullable|array|max:5',
            'usable_time_ranges.*.start' => 'required|date_format:H:i',
            'usable_time_ranges.*.end' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
            'device_once_enabled' => 'nullable|boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'target_product_ids.required_if' => '対象商品を1つ以上選択してください',
            'usable_time_ranges.max' => '使える時間帯は5つまでです',
            'usable_time_ranges.*.start.required' => '開始時刻を入力してください',
            'usable_time_ranges.*.end.required' => '終了時刻を入力してください',
            'usable_time_ranges.*.start.date_format' => '開始時刻は「時:分」で入力してください',
            'usable_time_ranges.*.end.date_format' => '終了時刻は「時:分」で入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('usable_time_ranges', []) as $index => $range) {
                $start = (string) ($range['start'] ?? '');
                $end = (string) ($range['end'] ?? '');
                if ($start !== '' && $end !== '' && $end <= $start) {
                    $validator->errors()->add("usable_time_ranges.{$index}.end", '終了時刻は開始時刻より後にしてください（日をまたぐ時間帯は2つに分けてください）');
                }
            }
        });
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
