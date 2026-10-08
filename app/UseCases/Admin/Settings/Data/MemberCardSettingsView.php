<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class MemberCardSettingsView
{
    public function __construct(
        public bool $showPointsDisplay,
        public bool $showRankProgress,
        public bool $showMemberCardForGuest,
        public string $memberCardDesign,
        public string $memberCardDisplayType,
        public string $memberCardCodeType,
        public bool $memberShowRankProgress,
        public bool $memberShowPointHistory,
        public bool $memberShowCouponActivity,
        public string $memberPageStyle = 'classic',
    ) {}
}
