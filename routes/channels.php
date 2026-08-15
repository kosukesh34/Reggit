<?php

use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('liff.member.{memberId}', function (User $user, string|int $memberId): bool {
    $member = $user->member;
    if ($member === null) {
        return false;
    }
    if ((int) $member->id !== (int) $memberId) {
        return false;
    }
    $projectId = AdminContext::getCurrentProjectId();
    if ($projectId !== null && (int) $member->project_id !== (int) $projectId) {
        return false;
    }

    return true;
});
