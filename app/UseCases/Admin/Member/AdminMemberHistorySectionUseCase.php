<?php

namespace App\UseCases\Admin\Member;

use App\Models\Member;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Admin\Member\Data\AdminMemberHistorySectionOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class AdminMemberHistorySectionUseCase
{
    public const SECTION_VISITS = 'visits';

    public const SECTION_POINTS = 'points';

    public const SECTION_COUPONS = 'coupons';

    public const SECTION_STAMPS = 'stamps';

    public const SECTION_RANK = 'rank';

    public const SECTION_REDEMPTIONS = 'redemptions';

    /** @var list<string> */
    public const SECTIONS = [
        self::SECTION_VISITS,
        self::SECTION_POINTS,
        self::SECTION_COUPONS,
        self::SECTION_STAMPS,
        self::SECTION_RANK,
        self::SECTION_REDEMPTIONS,
    ];

    public function __construct(
        private MemberAdminService $memberAdminService
    ) {}

    public function handle(
        int $projectId,
        int $memberId,
        string $section,
        string $keyword = '',
        string $from = '',
        string $to = '',
    ): AdminMemberHistorySectionOutput {
        if (! in_array($section, self::SECTIONS, true)) {
            throw new InvalidArgumentException('invalid history section');
        }

        $member = Member::query()
            ->where('project_id', $projectId)
            ->find($memberId);
        if ($member === null) {
            throw (new ModelNotFoundException)->setModel(Member::class, [$memberId]);
        }

        $keyword = trim($keyword);
        $from = trim($from);
        $to = trim($to);
        $paginator = match ($section) {
            self::SECTION_VISITS => $this->memberAdminService->getPaginatedVisits(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
            self::SECTION_POINTS => $this->memberAdminService->getPaginatedPointTransactions(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
            self::SECTION_COUPONS => $this->memberAdminService->getPaginatedMemberCoupons(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
            self::SECTION_STAMPS => $this->memberAdminService->getPaginatedStampTransactions(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
            self::SECTION_RANK => $this->memberAdminService->getPaginatedRankHistories(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
            self::SECTION_REDEMPTIONS => $this->memberAdminService->getPaginatedCouponRedemptions(
                $memberId, $keyword, MemberAdminService::HISTORY_PER_PAGE, 'page', $from, $to
            ),
        };

        $html = view('admin.partials.member-history.'.$section, [
            'member' => $member,
            'items' => $paginator,
            'paginationQuery' => array_filter([
                'q' => $keyword,
                'from' => $from,
                'to' => $to,
            ], static fn (string $v): bool => $v !== ''),
        ])->render();

        return new AdminMemberHistorySectionOutput($html, $paginator->total(), $section, $keyword, $from, $to);
    }
}
