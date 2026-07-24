<?php

namespace App\Http\Requests\Admin;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            $couponRaw = $prize['coupon_id'] ?? null;
            $isLose = $couponRaw === 'lose' || $couponRaw === '__lose__';

            if ($isLose) {
                $normalized[] = [
                    'reward_type' => (string) ($prize['reward_type'] ?? ''),
                    'coupon_id' => null,
                    'is_lose' => true,
                    'probability' => (int) ($prize['probability'] ?? 0),
                    'sort_order' => (int) ($prize['sort_order'] ?? 0),
                ];
                continue;
            }

            if ($couponRaw === null || $couponRaw === '') {
                continue;
            }

            $normalized[] = [
                'reward_type' => (string) ($prize['reward_type'] ?? ''),
                'coupon_id' => (int) $couponRaw,
                'is_lose' => false,
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
            'prizes.*.is_lose' => 'required|boolean',
            'prizes.*.coupon_id' => [
                'nullable',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $prizes = $this->input('prizes', []);
            if (! is_array($prizes)) {
                return;
            }
            foreach ($prizes as $index => $prize) {
                if (! is_array($prize)) {
                    continue;
                }
                $isLose = ($prize['is_lose'] ?? false) === true;
                $couponId = $prize['coupon_id'] ?? null;
                if ($isLose === true && $couponId !== null) {
                    $validator->errors()->add(
                        "prizes.{$index}.coupon_id",
                        'ハズレ景品にクーポンは設定できません。'
                    );
                }
                if ($isLose === false && $couponId === null) {
                    $validator->errors()->add(
                        "prizes.{$index}.coupon_id",
                        'クーポンを選択するか、ハズレを選んでください。'
                    );
                }
            }
        });
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
