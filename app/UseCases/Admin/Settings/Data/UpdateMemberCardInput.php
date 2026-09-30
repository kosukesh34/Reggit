<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateMemberCardInput
{
    public function __construct(
        public int $projectId,
        public ?bool $showPoints = null,
        public ?bool $showRankProgress = null,
        public ?bool $showForGuest = null,
        public ?bool $showScreenRankProgress = null,
        public ?bool $showPointHistory = null,
        public ?bool $showCouponActivity = null,
        public ?string $design = null,
        public ?string $displayType = null,
        public ?string $codeType = null,
    ) {}
}
