<?php

namespace App\UseCases\ShopAuth\Data;

final class ShopAuthUserView
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly bool $hasPasskey,
    ) {}
}
