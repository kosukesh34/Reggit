<?php

namespace App\UseCases\Liff\Data;

class LiffIndexInput
{
    public function __construct(
        public readonly string $userAgent,
    ) {}
}
