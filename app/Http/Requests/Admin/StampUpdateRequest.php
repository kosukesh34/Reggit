<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StampUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'required_stamps' => 'required|integer|min:1|max:999',
            'reward_description' => 'nullable|string|max:500',
            'reward_milestones' => 'nullable|array|max:20',
            'reward_milestones.*.stamp_count' => 'nullable|integer|min:1|max:999',
            'reward_milestones.*.description' => 'nullable|string|max:500',
            'image_url' => 'nullable|string|url|max:500',
            'layout_style' => 'nullable|string|in:grid,row,circle,minimal,card',
            'valid_days' => 'nullable|integer|min:1|max:3650',
            'valid_until' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $requiredStamps = (int) ($this->input('required_stamps') ?? 0);
            $milestones = $this->input('reward_milestones', []);
            if (!is_array($milestones)) {
                return;
            }

            $seen = [];
            foreach ($milestones as $index => $milestone) {
                if (!is_array($milestone)) {
                    continue;
                }
                $stampCount = (int) ($milestone['stamp_count'] ?? 0);
                $description = trim((string) ($milestone['description'] ?? ''));
                if ($stampCount <= 0 && $description === '') {
                    continue;
                }
                if ($stampCount <= 0) {
                    $validator->errors()->add(
                        "reward_milestones.{$index}.stamp_count",
                        '特典スタンプ数を入力してください。'
                    );
                    continue;
                }
                if ($description === '') {
                    $validator->errors()->add(
                        "reward_milestones.{$index}.description",
                        '特典内容を入力してください。'
                    );
                    continue;
                }
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
        });
    }
}
