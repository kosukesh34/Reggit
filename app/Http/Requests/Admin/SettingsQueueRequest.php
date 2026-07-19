<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SettingsQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'queue_avg_serve_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'queue_require_membership' => ['required', 'in:0,1'],
            'queue_channel_line' => ['nullable', 'in:0,1'],
            'queue_channel_native' => ['nullable', 'in:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'queue_avg_serve_minutes.required' => '平均対応時間を入力してください',
            'queue_avg_serve_minutes.integer' => '平均対応時間は整数で入力してください',
            'queue_avg_serve_minutes.min' => '平均対応時間は1以上で入力してください',
            'queue_avg_serve_minutes.max' => '平均対応時間は120以下で入力してください',
            'queue_require_membership.required' => '会員登録の要否を選択してください',
            'queue_require_membership.in' => '会員登録の要否を選択してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $line = $this->boolean('queue_channel_line');
            $native = $this->boolean('queue_channel_native');
            if (! $line && ! $native) {
                $validator->errors()->add('queue_channel_line', '起動アプリを1つ以上選択してください');
            }
        });
    }

    /**
     * @return array{
     *     queue_avg_serve_minutes: int,
     *     queue_require_membership: bool,
     *     queue_channel_line: bool,
     *     queue_channel_native: bool
     * }
     */
    public function validatedSettings(): array
    {
        $validated = $this->validated();

        return [
            'queue_avg_serve_minutes' => (int) $validated['queue_avg_serve_minutes'],
            'queue_require_membership' => $this->boolean('queue_require_membership'),
            'queue_channel_line' => $this->boolean('queue_channel_line'),
            'queue_channel_native' => $this->boolean('queue_channel_native'),
        ];
    }
}
