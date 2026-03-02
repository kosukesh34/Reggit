<?php

namespace App\UseCases\Admin\Account\Data;

/** アカウント削除 UseCase の出力。不変。 */
class AccountDestroyOutput
{
    public function __construct(
        public bool $success,
        public string $message
    ) {}
}
