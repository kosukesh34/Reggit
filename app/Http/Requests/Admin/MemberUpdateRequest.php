<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MemberUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = (int) $this->route('id');

        return [
            // ユーザー情報
            'name' => ['nullable', 'string', 'max:255'],
            'kana' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],

            // 会員情報
            'member_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('members', 'member_number')->ignore($memberId),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'rank' => ['nullable', 'string', 'max:64'],
            'birthday' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'prefecture' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:128'],
            'street' => ['nullable', 'string', 'max:256'],
            'sex' => ['nullable', 'string', 'max:16'],

            // ポイント操作（任意）
            'point_set' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'point_add' => ['nullable', 'integer', 'min:0'],
            'point_subtract' => ['nullable', 'integer', 'min:0'],
            'point_description' => ['nullable', 'string', 'max:255'],

            // 任意項目（JSON）
            'profile_extra' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_number.required' => '会員番号を入力してください',
            'member_number.unique' => 'この会員番号は既に使用されています',
            'status.in' => 'ステータスを選択してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('point_set')) {
                return;
            }
            $add = (int) $this->input('point_add', 0);
            $sub = (int) $this->input('point_subtract', 0);
            if ($add > 0 && $sub > 0) {
                $validator->errors()->add('point_add', 'ポイント加算と減算は同時に指定できません');
                $validator->errors()->add('point_subtract', 'ポイント加算と減算は同時に指定できません');
            }
        });
    }

    /**
     * ポイント操作を取得。直接指定 > 加算 > 減算 の優先順。
     */
    public function getPointAdjustment(): ?array
    {
        if ($this->filled('point_set')) {
            return [
                'type' => 'set',
                'points' => max(0, (int) $this->input('point_set')),
                'description' => $this->input('point_description'),
            ];
        }

        $add = (int) $this->input('point_add', 0);
        $sub = (int) $this->input('point_subtract', 0);
        if ($add > 0) {
            return ['type' => 'add', 'points' => $add, 'description' => $this->input('point_description')];
        }
        if ($sub > 0) {
            return ['type' => 'subtract', 'points' => $sub, 'description' => $this->input('point_description')];
        }

        return null;
    }

    /**
     * profile_extra を配列で取得（JSON 文字列をデコード）
     */
    public function getProfileExtraArray(): ?array
    {
        $raw = $this->input('profile_extra');
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
