<?php

namespace App\Http\Requests\Admin;

use App\Enums\StampRewardType;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StampStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = (int) (AdminContext::getCurrentProjectId() ?? 0);
        $couponExists = Rule::exists('coupons', 'id');
        if ($projectId > 0) {
            $couponExists = $couponExists->where('project_id', $projectId);
        }

        return [
            'name' => 'required|string|max:255',
            'required_stamps' => 'required|integer|min:1|max:999',
            'reward_description' => 'nullable|string|max:500',
            'reward_milestones' => 'nullable|array|max:20',
            'reward_milestones.*.stamp_count' => 'nullable|integer|min:1|max:999',
            'reward_milestones.*.reward_type' => ['nullable', 'string', Rule::in(StampRewardType::keys())],
            'reward_milestones.*.coupon_id' => ['nullable', 'integer', $couponExists],
            'image_url' => 'nullable|string|url|max:500',
            'image_data' => 'nullable|string',
            'stamp_images_data' => 'nullable|string',
            'stamp_empty_images_data' => 'nullable|string',
            'layout_style' => 'nullable|string|in:grid,row,circle,minimal,card',
            'valid_days' => 'nullable|integer|min:1|max:3650',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateMilestones($validator);
        });
    }

    private function validateMilestones(Validator $validator): void
    {
        $requiredStamps = (int) ($this->input('required_stamps') ?? 0);
        $milestones = $this->input('reward_milestones', []);
        if (! is_array($milestones)) {
            return;
        }

        $seen = [];
        foreach ($milestones as $index => $milestone) {
            if (! is_array($milestone) || $this->isBlankMilestone($milestone)) {
                continue;
            }
            $stampCount = (int) ($milestone['stamp_count'] ?? 0);
            if ($stampCount <= 0) {
                $validator->errors()->add(
                    "reward_milestones.{$index}.stamp_count",
                    '特典スタンプ数を入力してください。'
                );

                continue;
            }
            $this->validateMilestoneReward($validator, (int) $index, $milestone);
            if ($requiredStamps > 0 && $stampCount > $requiredStamps) {
                $validator->errors()->add(
                    "reward_milestones.{$index}.stamp_count",
                    '特典スタンプ数は必要スタンプ数以下にしてください。'
                );
            }
            if (isset($seen[$stampCount])) {
                $validator->errors()->add(
                    "reward_milestones.{$index}.stamp_count",
                    '同じスタンプ数の特典は1つだけ登録できます。'
                );
            }
            $seen[$stampCount] = true;
        }
    }

    /** @param array<string, mixed> $milestone */
    private function isBlankMilestone(array $milestone): bool
    {
        $stampCount = (int) ($milestone['stamp_count'] ?? 0);
        $couponId = (int) ($milestone['coupon_id'] ?? 0);
        $rewardType = (string) ($milestone['reward_type'] ?? '');

        return $stampCount <= 0
            && $couponId <= 0
            && $rewardType !== StampRewardType::GACHA;
    }

    /** @param array<string, mixed> $milestone */
    private function validateMilestoneReward(Validator $validator, int $index, array $milestone): void
    {
        $rewardType = StampRewardType::normalize((string) ($milestone['reward_type'] ?? ''));
        if ($rewardType === StampRewardType::GACHA) {
            return;
        }
        if ((int) ($milestone['coupon_id'] ?? 0) <= 0) {
            $validator->errors()->add(
                "reward_milestones.{$index}.coupon_id",
                '特典クーポンを選択してください。'
            );
        }
    }
}
