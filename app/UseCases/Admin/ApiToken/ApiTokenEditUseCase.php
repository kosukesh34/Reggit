<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;

class ApiTokenEditUseCase
{
    public function handle(int $id): ApiToken
    {
        return ApiToken::with('project')->findOrFail($id);
    }
}
