<?php

namespace App\Http\Requests\Landing;

use App\Enums\LandingInquiryPlan;
use App\Models\AdminUser;
use App\Models\LandingRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Validator;

class LandingRegistrationFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function redirectTo(): string
    {
        $registration = $this->route('registration');
        if (! $registration instanceof LandingRegistration) {
            return '/#register';
        }

        return URL::temporarySignedRoute(
            'landing.register.form',
            now()->addDays(LandingRegistration::FORM_EXPIRES_DAYS),
            ['registration' => $registration->id],
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $registration = $this->route('registration');
            if (! $registration instanceof LandingRegistration || $registration->submitted_at !== null) {
                return;
            }
            if (AdminUser::query()->where('email', $registration->email)->exists()) {
                $validator->errors()->add(
                    'email',
                    'このメールアドレスはすでに管理者アカウントで使われています。',
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'plan' => ['required', 'string', 'in:'.implode(',', array_keys(LandingInquiryPlan::getValues()))],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => '店舗名・会社名を入力してください',
            'contact_name.required' => 'ご担当者名を入力してください',
            'phone.required' => '電話番号を入力してください',
            'plan.required' => '希望プランを選択してください',
            'plan.in' => '希望プランの値が正しくありません',
        ];
    }

    /**
     * @return array{
     *     store_name: string,
     *     contact_name: string,
     *     phone: string,
     *     plan: string,
     *     message: string|null,
     * }
     */
    public function toFormPayload(): array
    {
        return [
            'store_name' => (string) $this->validated('store_name'),
            'contact_name' => (string) $this->validated('contact_name'),
            'phone' => (string) $this->validated('phone'),
            'plan' => (string) $this->validated('plan'),
            'message' => $this->validated('message') !== null ? (string) $this->validated('message') : null,
        ];
    }
}
