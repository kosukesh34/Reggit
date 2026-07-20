<?php

namespace App\UseCases\Admin\Settings;

use App\Models\Project;
use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\LiffConnectionSettingsView;

class GetLiffConnectionSettingsUseCase
{
    public function handle(int $projectId): LiffConnectionSettingsView
    {
        $project = Project::query()->find($projectId);
        $projectKey = $project !== null ? (string) $project->project_key : '';
        $liffEntryUrl = $projectKey !== ''
            ? url('/liff/'.$projectKey)
            : url('/liff');

        $dbToken = trim((string) ShopSetting::getValue('line_channel_access_token', '', $projectId));
        $dbSecret = trim((string) ShopSetting::getValue('line_channel_secret', '', $projectId));

        return new LiffConnectionSettingsView(
            ShopSetting::getLineLiffId($projectId),
            ShopSetting::getLineLiffUrl($projectId),
            $liffEntryUrl,
            ShopSetting::getLineFriendAddUrl($projectId),
            $dbToken !== '',
            $dbSecret !== '',
        );
    }
}
