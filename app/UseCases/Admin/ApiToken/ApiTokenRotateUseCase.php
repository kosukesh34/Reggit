<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;
use App\Models\Project;
use App\UseCases\Admin\ApiToken\Data\ApiTokenIssuedResult;

class ApiTokenRotateUseCase
{
    public function handle(int $id): ApiTokenIssuedResult
    {
        $token = ApiToken::findOrFail($id);
        $token->update(['is_active' => false]);

        $project = Project::findOrFail($token->project_id);
        $issued = ApiToken::issue($project, $token->name . ' (rotated)', $token->expires_at);

        return new ApiTokenIssuedResult($issued['plain'], (int) $issued['token']->id);
    }
}
