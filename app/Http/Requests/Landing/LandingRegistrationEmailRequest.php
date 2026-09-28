<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class LandingRegistrationEmailRequest extends FormRequest
{
    protected $redirectRoute = 'landing.register.email.show';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式が正しくありません',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
        ];
    }

    public function emailAddress(): string
    {
        return mb_strtolower(trim((string) $this->validated('email')));
    }
}
