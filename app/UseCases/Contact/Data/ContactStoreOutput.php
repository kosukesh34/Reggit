<?php

namespace App\UseCases\Contact\Data;

/** お問い合わせ送信 UseCase の出力。不変。 */
class ContactStoreOutput
{
    public function __construct(public bool $success) {}
}
