<?php

namespace App\UseCases\Admin\ApiToken\Dto;

class ApiTokenIssuedDto
{
    public function __construct(
        public string $plain,
        public int $tokenId
    ) {
    }
}
