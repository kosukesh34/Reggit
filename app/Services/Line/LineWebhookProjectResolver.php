<?php

namespace App\Services\Line;

use App\Models\Settings\ProjectLineLiffSetting;
use App\Repositories\Contracts\ProjectRepository;

class LineWebhookProjectResolver
{
    public function __construct(
        private LineService $lineService,
        private ProjectRepository $projectRepository,
    ) {}

    public function resolveProjectId(string $body, ?string $signature): ?int
    {
        // ponytail: 全店舗 secret を HMAC 照合。天井は店舗数。LINE destination を店舗に保存して索引すれば O(1)
        foreach (ProjectLineLiffSetting::query()->get(['project_id', 'line_channel_secret']) as $setting) {
            $secret = trim((string) ($setting->line_channel_secret ?? ''));
            if ($secret === '') {
                continue;
            }
            if ($this->lineService->verifySignatureWithSecret($body, $signature, $secret) === true) {
                return (int) $setting->project_id;
            }
        }

        if ($this->lineService->verifySignature($body, $signature) !== true) {
            return null;
        }

        $project = $this->projectRepository->findFirstActive();

        return $project !== null ? (int) $project->id : null;
    }
}
