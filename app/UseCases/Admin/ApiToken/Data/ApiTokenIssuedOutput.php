<?php

namespace App\UseCases\Admin\ApiToken\Data;

/** API トークン発行・ローテートの出力。不変。 */
class ApiTokenIssuedOutput
{
    public function __construct(
        public string $plain,
        public int $tokenId
    ) {
    }
}
