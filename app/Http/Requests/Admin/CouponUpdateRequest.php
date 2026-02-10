<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 0;
        $couponId = (int) $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons')->where('project_id', $projectId)->ignore($couponId),
            ],
            'name' => 'nullable|string|max:255',
            'image_url' => 'nullable|url|max:500',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'is_active' => 'nullable',
            'issue_trigger' => 'required|in:manual,first_registration,stamp_completed',
            'stamp_card_id' => 'nullable|exists:stamp_cards,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes('stamp_card_id', 'required|exists:stamp_cards,id', function ($input) {
            return ($input->issue_trigger ?? '') === Coupon::ISSUE_TRIGGER_STAMP_COMPLETED;
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);
        if ($key !== null) {
            return $validated;
        }
        if (($validated['issue_trigger'] ?? '') !== Coupon::ISSUE_TRIGGER_STAMP_COMPLETED) {
            $validated['stamp_card_id'] = null;
        }
        return $validated;
    }
}
