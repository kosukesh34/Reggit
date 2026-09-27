<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\LineAdminSettings;
use App\Models\Project;
use App\UseCases\Admin\Settings\Data\LiffConnectionSettingsView;

class GetLiffConnectionSettingsUseCase
{
    public function __construct(
        private LineAdminSettings $shopSettingRepository,
    ) {}

    public function handle(int $projectId): LiffConnectionSettingsView
    {
        $project = Project::query()->find($projectId);
        $projectKey = $project !== null ? (string) $project->project_key : '';
        $liffEntryUrl = $projectKey !== ''
            ? url('/liff/'.$projectKey)
            : url('/liff');

        $repo = $this->shopSettingRepository;
        $dbToken = trim((string) $repo->getValue('line_channel_access_token', '', $projectId));
        $dbSecret = trim((string) $repo->getValue('line_channel_secret', '', $projectId));

        return new LiffConnectionSettingsView(
            $repo->getLineLiffId($projectId),
            $repo->getLineLiffUrl($projectId),
            $liffEntryUrl,
            $repo->getLineFriendAddUrl($projectId),
            $dbToken !== '',
            $dbSecret !== '',
        );
    }
}
