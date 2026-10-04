<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Settings\ProjectLineLiffSetting;

class ProjectQrAppUrl
{
    public static function lineUrl(int $projectId): string
    {
        $line = ProjectLineLiffSetting::query()->where('project_id', $projectId)->first();
        $url = trim((string) $line?->line_liff_url);
        $id = trim((string) $line?->line_liff_id);

        return $url !== '' ? $url : ($id !== '' ? 'https://liff.line.me/'.rawurlencode($id) : '');
    }

    /** @param array<string, string> $extra */
    public static function tabUrl(Project $project, string $tab, array $extra = []): string
    {
        $baseUrl = self::lineUrl((int) $project->id);
        if ($baseUrl === '') {
            $baseUrl = route('liff.project', ['project_key' => $project->project_key]);
        }

        return LiffTabUrlBuilder::buildFromBaseUrl($baseUrl, $project->project_key, $tab, $extra);
    }
}
