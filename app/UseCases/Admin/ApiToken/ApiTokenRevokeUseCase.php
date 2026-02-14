<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;

class ApiTokenRevokeUseCase
{
    public function handle(int $id): ApiToken
    {
        $token = ApiToken::findOrFail($id);
        $token->update(['is_active' => false]);

        return $token;
    }
}
