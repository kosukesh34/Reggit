<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class StoreAppCredentialView
{
    public function __construct(public string $status) {}
}
