<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\MemberCardSettings;
use App\UseCases\Admin\Settings\Data\MemberCardSettingsView;

class GetMemberCardSettingsUseCase
{
    public function __construct(private MemberCardSettings $settings) {}

    public function handle(int $projectId): MemberCardSettingsView
    {
        return new MemberCardSettingsView(
            $this->settings->getShowPointsDisplay($projectId),
            $this->settings->getShowRankProgress($projectId),
            $this->settings->getShowMemberCardForGuest($projectId),
            $this->settings->getMemberCardDesign($projectId),
            $this->settings->getMemberCardDisplayType($projectId),
            $this->settings->getMemberCardCodeType($projectId),
            (bool) $this->settings->getValue('member_show_rank_progress', true, $projectId),
            (bool) $this->settings->getValue('member_show_point_history', true, $projectId),
            (bool) $this->settings->getValue('member_show_coupon_activity', true, $projectId),
        );
    }
}
