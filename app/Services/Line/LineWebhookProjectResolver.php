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

    public function resolveProjectId(string $body, ?string $signature): ?int
    {
        $matchedId = null;
        $projects = Project::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id']);
        foreach ($projects as $project) {
            $projectId = (int) $project->id;
            $secret = trim($this->shopSettingRepository->getLineChannelSecret($projectId));
            if ($secret === '') {
                continue;
            }
            if ($this->lineService->verifySignatureWithSecret($body, $signature, $secret) !== true) {
                continue;
            }
            if ($matchedId !== null && $matchedId !== $projectId) {
                return null;
            }
            $matchedId = $projectId;
        }

        return $matchedId;
    }
}
