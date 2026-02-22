<?php

namespace App\UseCases\Contact\Data;

/** お問い合わせ送信 UseCase の実行結果。不変。 */
class ContactStoreResult
{
    public function __construct(public bool $success)
    {
    }
}
