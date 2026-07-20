<?php

namespace App\UseCases\Admin\Member;

use App\Models\Project;
use App\Models\ShopSetting;
use App\UseCases\Admin\Member\Data\AdminMemberListExtras;

class GetAdminMemberListExtrasUseCase
{
    public function handle(int $projectId): AdminMemberListExtras
    {
        $friendAddUrl = trim(ShopSetting::getLineFriendAddUrl($projectId));
        if ($friendAddUrl !== '') {
            return new AdminMemberListExtras($friendAddUrl, 'LINE友だち追加URL');
        }

        $liffUrl = trim(ShopSetting::getLineLiffUrl($projectId));
        if ($liffUrl !== '') {
            return new AdminMemberListExtras($liffUrl, 'LIFF URL');
        }

        $projectKey = Project::query()->where('id', $projectId)->value('project_key');
        $fallback = is_string($projectKey) && $projectKey !== ''
            ? url('/liff/'.$projectKey)
            : url('/liff');

        return new AdminMemberListExtras($fallback, 'LIFFエントリURL');
    }
}
