<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberExportUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberListView;
use App\UseCases\Admin\Member\GetAdminMemberListExtrasUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
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
        private AdminMemberTagIndexUseCase $tagIndexUseCase,
        private GetAdminMemberListExtrasUseCase $memberListExtrasUseCase,
    ) {}

    public function index(Request $request): View
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(self::FILTERS);
        $output = $this->listUseCase->handle($projectId, $filters);
        $extras = $this->memberListExtrasUseCase->handle($projectId);

        return view('admin.members', ['view' => new AdminMemberListView(
            $output->members,
            $filters,
            $extras->lineInviteUrl,
            $extras->lineInviteLabel,
            $output->rankOptions,
            $this->tagIndexUseCase->handle($projectId),
        )]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->exportUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->only(self::FILTERS),
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
