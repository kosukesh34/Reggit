<?php

namespace App\UseCases\Admin\ApiToken;

use App\Models\ApiToken;
use App\Models\Project;
use App\UseCases\Admin\ApiToken\Dto\ApiTokenIssuedDto;

class ApiTokenStoreUseCase
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): ApiTokenIssuedDto
    {
        $project = Project::findOrFail((int) $payload['project_id']);
        $expiresAt = $payload['expires_at'] ? new \DateTimeImmutable($payload['expires_at']) : null;
        $issued = ApiToken::issue($project, (string) $payload['name'], $expiresAt);

        return new ApiTokenIssuedDto($issued['plain'], (int) $issued['token']->id);
    }
}
