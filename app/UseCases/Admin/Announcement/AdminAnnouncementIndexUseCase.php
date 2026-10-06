<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\Announcement;
use App\Models\MemberAudienceGroup;
use App\Services\Admin\AnnouncementAdminService;
use App\UseCases\Admin\Announcement\Data\AdminAnnouncementIndexOutput;
use App\Util\AdminContext;

class AdminAnnouncementIndexUseCase
{
    private const PER_PAGE = 20;

    /** @var array<string, string> */
    private const DELIVERY_TYPE_LABELS = [
        'all' => 'すべて',
        Announcement::DELIVERY_TYPE_IMMEDIATE_BULK => '全会員',
        Announcement::DELIVERY_TYPE_PERSONAL => '個別指定',
        Announcement::DELIVERY_TYPE_SEGMENT => '条件指定',
        Announcement::DELIVERY_TYPE_AFTER_REGISTRATION => 'スケジュール配信',
    ];

    /** @var array<string, string> */
    private const DELIVERY_STATUS_LABELS = [
        Announcement::DELIVERY_STATUS_SENT => '配信済み',
        Announcement::DELIVERY_STATUS_SCHEDULED => '予約中',
        Announcement::DELIVERY_STATUS_DRAFT => '下書き',
    ];

    public function __construct(
        private AnnouncementAdminService $announcementAdminService,
    ) {}

    /** @param  array<string, string|null>  $input */
    public function handle(array $input): AdminAnnouncementIndexOutput
    {
        $filters = $this->announcementAdminService->normalizeIndexFilters($input);

        return new AdminAnnouncementIndexOutput(
            $this->announcementAdminService->getPaginatedList($filters, self::PER_PAGE),
            $filters,
            $this->announcementAdminService->getDeliveryTypeCounts($filters),
            self::DELIVERY_TYPE_LABELS,
            self::DELIVERY_STATUS_LABELS,
            MemberAudienceGroup::query()
                ->where('project_id', AdminContext::resolveProjectId(null))
                ->orderBy('name')
                ->get(),
        );
    }
}
