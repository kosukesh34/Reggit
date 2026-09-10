<?php

namespace App\UseCases\Landing\Data;

/** LP 登録完了画面用。パスワードは初回のみ。 */
class LandingRegistrationCompleteView
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $storeName,
        public readonly string $liffUrl,
        public readonly string $loginUrl,
        public readonly string $loginId,
        public readonly ?string $password,
    ) {}
}
