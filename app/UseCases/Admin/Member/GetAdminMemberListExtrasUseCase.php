<?php

namespace App\UseCases\Admin\Member;

use App\Services\Admin\MemberSegmentFormOptionsService;
use App\UseCases\Admin\Member\Data\AdminMemberListExtras;

class GetAdminMemberListExtrasUseCase
{
    public function __construct(
        private MemberSegmentFormOptionsService $segmentFormOptions,
    ) {}

    /** @param  bool  $withAudienceGroups  配信グループの作成では、グループの中にグループは入れられないため false */
    public function handle(int $projectId, bool $withAudienceGroups = true): AdminMemberListExtras
    {
        $options = $this->segmentFormOptions->forProject($projectId, $withAudienceGroups);

        return new AdminMemberListExtras(
            $options->memberTags,
            $options->products,
            $options->coupons,
            $options->rfmSegments,
            $options->profileFields,
            $options->audienceGroups,
        );
    }
}
