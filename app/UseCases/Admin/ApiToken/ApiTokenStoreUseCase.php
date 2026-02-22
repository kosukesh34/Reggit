<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;
use App\Models\Project;
use App\UseCases\Admin\ApiToken\Data\ApiTokenIssuedResult;

class ApiTokenStoreUseCase
{
    /** @param array<string, string|int|float|bool|array|null> $payload トークン作成用ペイロード（name, project_id 等） */
    public function handle(array $payload): ApiTokenIssuedResult
    {
        $project = Project::findOrFail((int) $payload['project_id']);
        $expiresAt = $payload['expires_at'] ? new \DateTimeImmutable($payload['expires_at']) : null;
        $issued = ApiToken::issue($project, (string) $payload['name'], $expiresAt);

        return new ApiTokenIssuedResult($issued['plain'], (int) $issued['token']->id);
    }
}
