<?php

namespace App\UseCases\Landing\Data;

/** LP 入力フォーム画面用。 */
class LandingRegistrationFormView
{
    /**
     * @param  list<array{value: string, label: string, description: string}>  $planOptions
     */
    public function __construct(
        public readonly string $formAction,
        public readonly string $email,
        public readonly string $storeName,
        public readonly string $contactName,
        public readonly string $phone,
        public readonly string $plan,
        public readonly string $message,
        public readonly array $planOptions,
    ) {}
}
