<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\MemberCardSettings;
use App\Services\Admin\LiffSettingsService;
use App\UseCases\Admin\Settings\Data\UpdateMemberCardInput;

class UpdateMemberCardUseCase
{
    public function __construct(private MemberCardSettings $settings, private LiffSettingsService $liff) {}

    public function handle(UpdateMemberCardInput $input): void
    {
        foreach ([
            'show_points_display' => $input->showPoints,
            'show_rank_progress' => $input->showRankProgress,
            'member_show_rank_progress' => $input->showScreenRankProgress,
            'member_show_point_history' => $input->showPointHistory,
            'member_show_coupon_activity' => $input->showCouponActivity,
        ] as $key => $value) {
            if ($value !== null) {
                $this->settings->setValue($key, $value, 'boolean', $input->projectId);
            }
        }
        if ($input->showForGuest !== null) {
            $this->settings->setShowMemberCardForGuest($input->showForGuest, $input->projectId);
        }
        if ($input->design !== null) {
            $this->settings->setMemberCardDesign($input->design, $input->projectId);
            $this->liff->syncMembershipTemplateFromDesign($input->design, $input->projectId);
        }
        if ($input->displayType !== null) {
            $this->settings->setMemberCardDisplayType($input->displayType, $input->projectId);
        }
        if ($input->pageStyle !== null) {
            $tabs = (array) $this->settings->getValue('liff_tab_settings', [], $input->projectId);
            $tabs['_global']['member_page_style'] = $input->pageStyle;
            $this->settings->setValue('liff_tab_settings', $tabs, 'json', $input->projectId);
        }
        if ($input->codeType !== null) {
            $this->settings->setMemberCardCodeType($input->codeType, $input->projectId);
        }
    }
}
