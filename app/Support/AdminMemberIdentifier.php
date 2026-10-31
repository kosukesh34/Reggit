<?php

namespace App\Support;

use App\Infrastructure\Persistence\Eloquent\Settings\MemberCodeSettingStore;
use App\Models\Member;
use App\Util\AdminContext;

class AdminMemberIdentifier
{
    public static function usesId(?int $projectId = null): bool
    {
        $projectId ??= AdminContext::getCurrentProjectId();

        return $projectId !== null && MemberCodeSettingStore::getMemberCodeType($projectId) === 'sequential';
    }

    public static function label(?int $projectId = null): string
    {
        return self::usesId($projectId) ? '会員ID' : '会員番号';
    }

    public static function value(?Member $member): string
    {
        return $member === null ? '—' : self::fromValues($member->id, $member->member_number, $member->project_id);
    }

    public static function fromValues(?int $id, ?string $number, ?int $projectId = null): string
    {
        return self::usesId($projectId) ? ($id === null ? '—' : (string) $id) : (trim((string) $number) ?: '—');
    }
}
