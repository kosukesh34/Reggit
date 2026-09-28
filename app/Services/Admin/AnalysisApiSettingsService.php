<?php

namespace App\Services\Admin;

use App\Models\ApiToken;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class AnalysisApiSettingsService
{
    public function project(int $projectId): Project
    {
        return Project::findOrFail($projectId);
    }

    public function activeTokens(int $projectId)
    {
        return ApiToken::where('project_id', $projectId)->where('scope', 'analysis')->where('is_active', true)->get(['id', 'name', 'expires_at']);
    }

    public function update(int $projectId, bool $enabled, bool $deliveryEnabled): void
    {
        Project::whereKey($projectId)->update([
            'analysis_api_enabled' => $enabled,
            'analysis_delivery_enabled' => $enabled && $deliveryEnabled,
        ]);
    }

    public function issueToken(int $projectId): string
    {
        return DB::transaction(function () use ($projectId): string {
            $project = $this->project($projectId);
            ApiToken::where('project_id', $project->id)->where('scope', 'analysis')->update(['is_active' => false]);

            return ApiToken::issue($project, '分析MCP', now()->addDays(90), 'analysis')['plain'];
        });
    }

    public function revokeToken(int $projectId, int $tokenId): void
    {
        ApiToken::where('project_id', $projectId)->where('scope', 'analysis')->whereKey($tokenId)->update(['is_active' => false]);
    }
}
