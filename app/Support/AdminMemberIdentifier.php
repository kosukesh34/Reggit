<?php

namespace App\Support;

use App\Domain\Settings\Repositories\MemberCodeSettings;
use App\Models\Member;
use App\Util\AdminContext;

/** 管理画面で会員を示す識別子（連番設定なら会員ID、それ以外は会員番号）。 */
class AdminMemberIdentifier
{
    public function __construct(private MemberCodeSettings $memberCodeSettings) {}

    public function usesId(?int $projectId = null): bool
    {
        $projectId ??= AdminContext::getCurrentProjectId();

        return $projectId !== null && $this->memberCodeSettings->getMemberCodeType($projectId) === 'sequential';
    }

    public function label(?int $projectId = null): string
    {
        return $this->usesId($projectId) ? '会員ID' : '会員番号';
    }

    public function value(?Member $member): string
    {
        return $member === null ? '—' : $this->fromValues($member->id, $member->member_number, $member->project_id);
    }

    public function fromValues(?int $id, ?string $number, ?int $projectId = null): string
    {
        return $this->usesId($projectId) ? ($id === null ? '—' : (string) $id) : (trim((string) $number) ?: '—');
    }
}
