<?php

namespace App\Services\Line;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Models\Project;

class LineWebhookProjectResolver
{
    public function __construct(
        private LineService $lineService,
        private LineConnectionSettings $shopSettingRepository,
    ) {}

    /** project_key 付き URL では、その店舗の Secret だけで署名を確かめる */
    public function resolveProjectIdByKey(string $projectKey, string $body, ?string $signature): ?int
    {
        $projectId = Project::query()
            ->where('project_key', $projectKey)
            ->where('is_active', true)
            ->value('id');

        return $projectId !== null && $this->verifies((int) $projectId, $body, $signature) ? (int) $projectId : null;
    }

    public function resolveProjectId(string $body, ?string $signature): ?int
    {
        $matchedId = null;
        $projects = Project::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id']);
        foreach ($projects as $project) {
            $projectId = (int) $project->id;
            if (! $this->verifies($projectId, $body, $signature)) {
                continue;
            }
            if ($matchedId !== null && $matchedId !== $projectId) {
                return null;
            }
            $matchedId = $projectId;
        }

        return $matchedId;
    }

    private function verifies(int $projectId, string $body, ?string $signature): bool
    {
        $secret = trim($this->shopSettingRepository->getLineChannelSecret($projectId));

        return $secret !== '' && $this->lineService->verifySignatureWithSecret($body, $signature, $secret) === true;
    }
}
