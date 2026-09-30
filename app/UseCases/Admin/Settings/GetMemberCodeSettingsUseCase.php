<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\MemberCodeSettings;
use App\UseCases\Admin\Settings\Data\MemberCodeSettingsView;

class GetMemberCodeSettingsUseCase
{
    public function __construct(private MemberCodeSettings $settings) {}

    public function handle(int $projectId): MemberCodeSettingsView
    {
        return new MemberCodeSettingsView($projectId, $this->settings->getMemberCodeType($projectId), $this->settings->getMemberCodeFormat($projectId), $this->settings->getMemberCodeLength($projectId), $this->settings->getMemberCodePrefix($projectId));
    }
}
