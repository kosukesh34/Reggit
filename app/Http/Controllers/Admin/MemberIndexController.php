<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberExportUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberListView;
use App\UseCases\Admin\Member\GetAdminMemberListExtrasUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberIndexController extends AdminBaseController
{
    private const FILTERS = [
        'keyword', 'rank', 'channel', 'tag_id',
        'visited_from', 'visited_to', 'sort', 'dir',
    ];

    public function __construct(
        private AdminMemberListUseCase $listUseCase,
        private AdminMemberExportUseCase $exportUseCase,
        private GetAdminMemberListExtrasUseCase $memberListExtrasUseCase,
    ) {}

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        // 配信と共通の条件ビルダーの入力（segment_*）。query() だけでなく input 全体から拾う
        $filters = $request->only(self::FILTERS);
        foreach ($request->all() as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'segment_')) {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    public function index(Request $request): View
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $this->filters($request);
        $output = $this->listUseCase->handle($projectId, $filters);
        $extras = $this->memberListExtrasUseCase->handle($projectId);

        return view('admin.members', ['view' => new AdminMemberListView(
            $output->members,
            $filters,
            $extras->lineInviteUrl,
            $extras->lineInviteLabel,
            $output->rankOptions,
            $extras->memberTags,
            $extras->products,
            $extras->coupons,
            $extras->rfmSegments,
            $extras->profileFields,
            $extras->audienceGroups,
            $output->hasSegmentFilters,
        )]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->exportUseCase->handle(
            $this->resolveAdminProjectId(),
            $this->filters($request),
        );
        $csvHeader = array_keys($rows->first() ?? []);

        $callback = function () use ($rows, $csvHeader): void {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $csvHeader);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="members_'.now()->format('Ymd_His').'.csv"',
        ]);
    }
}
