<?php

namespace App\Http\Requests\Admin;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LotterySettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prizes = $this->input('prizes', []);
        if (! is_array($prizes)) {
            $prizes = [];
        }
        $normalized = [];
        foreach ($prizes as $prize) {
            if (! is_array($prize)) {
                continue;
            }
            $couponId = $prize['coupon_id'] ?? null;
            if ($couponId === null || $couponId === '') {
                continue;
            }
            $normalized[] = [
                'reward_type' => (string) ($prize['reward_type'] ?? ''),
                'coupon_id' => (int) $couponId,
                'probability' => (int) ($prize['probability'] ?? 0),
                'sort_order' => (int) ($prize['sort_order'] ?? 0),
            ];
        }

        $this->merge([
            'gacha_enabled' => $this->boolean('gacha_enabled'),
            'scratch_enabled' => $this->boolean('scratch_enabled'),
            'prizes' => $normalized,
            'min_amount' => $this->filled('min_amount') ? $this->input('min_amount') : null,
            'entitlement_expires_hours' => $this->filled('entitlement_expires_hours')
                ? $this->input('entitlement_expires_hours')
                : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $projectId = (int) (AdminContext::getCurrentProjectId() ?? 0);

        return [
            'gacha_enabled' => 'required|boolean',
            'scratch_enabled' => 'required|boolean',
            'grant_trigger' => ['required', 'string', Rule::in(LotteryGrantTrigger::keys())],
            'min_amount' => 'nullable|numeric|min:0',
            'entitlement_expires_hours' => 'nullable|integer|min:1|max:8760',
            'prizes' => 'nullable|array',
            'prizes.*.reward_type' => ['required', 'string', Rule::in(LotteryRewardType::keys())],
            'prizes.*.coupon_id' => [
                'required',
                'integer',
                Rule::exists('coupons', 'id')->where(function ($query) use ($projectId) {
                    if ($projectId > 0) {
                        $query->where('project_id', $projectId);
                    }
                }),
            ],
            'prizes.*.probability' => 'required|integer|min:1|max:100',
            'prizes.*.sort_order' => 'nullable|integer|min:0',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'prizes.*.probability.min' => '確率は1以上で入力してください。',
            'prizes.*.coupon_id.exists' => '選択したクーポンが不正です。',
        ];
    }
}
