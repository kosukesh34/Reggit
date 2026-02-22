<?php

namespace App\UseCases\Admin\ApiToken\Data;

/** API トークン発行・ローテートの実行結果。不変。 */
class ApiTokenIssuedResult
{
    public function __construct(
        public string $plain,
        public int $tokenId
    ) {
    }
}
