<?php

namespace App\UseCases\Admin\Account\Data;

/** アカウント削除 UseCase の実行結果。不変。 */
class AccountDestroyResult
{
    public function __construct(
        public bool $success,
        public string $message
    ) {
    }
}
