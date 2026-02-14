<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;

class ApiTokenUpdateUseCase
{
    /** @param array<string, mixed> $payload */
    public function handle(int $id, array $payload): ApiToken
    {
        $token = ApiToken::findOrFail($id);
        $token->update($payload);

        return $token;
    }
}
