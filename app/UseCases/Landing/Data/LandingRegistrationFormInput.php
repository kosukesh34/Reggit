<?php

namespace App\UseCases\Landing\Data;

use App\Models\LandingRegistration;

/**
 * LP 入力フォーム送信の入力。検証済み。
 *
 * @param  array{
 *     store_name: string,
 *     contact_name: string,
 *     phone: string,
 *     plan: string,
 *     message: string|null,
 * }  $formPayload
 */
class LandingRegistrationFormInput
{
    /**
     * @param  array{
     *     store_name: string,
     *     contact_name: string,
     *     phone: string,
     *     plan: string,
     *     message: string|null,
     * }  $formPayload
     */
    public function __construct(
        public readonly LandingRegistration $registration,
        public readonly array $formPayload,
    ) {}
}
