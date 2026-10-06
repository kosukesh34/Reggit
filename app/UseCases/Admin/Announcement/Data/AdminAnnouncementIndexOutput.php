<?php

namespace App\UseCases\Admin\Announcement\Data;

use App\Models\MemberAudienceGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminAnnouncementIndexOutput
{
    /**
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     * @param  array<string, int>  $deliveryTypeCounts
     * @param  array<string, string>  $deliveryTypeLabels
     * @param  array<string, string>  $deliveryStatusLabels
     * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
     */
    public function __construct(
        public LengthAwarePaginator $announcements,
        public array $filters,
        public array $deliveryTypeCounts,
        public array $deliveryTypeLabels,
        public array $deliveryStatusLabels,
        public Collection $audienceGroups,
    ) {}
}
