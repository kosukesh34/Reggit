<?php

namespace App\UseCases\Landing\Data;

/** LP 登録の完了・案内画面用。 */
class LandingRegistrationStatusView
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $actionUrl,
        public readonly ?string $actionLabel,
    ) {}
}
