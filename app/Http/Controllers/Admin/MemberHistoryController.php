<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberHistorySectionUseCase;
use App\UseCases\Admin\Member\AdminMemberVisitDetailUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class MemberHistoryController extends AdminBaseController
{
    public function __construct(
        private AdminMemberHistorySectionUseCase $historySectionUseCase,
        private AdminMemberVisitDetailUseCase $visitDetailUseCase,
    ) {}

    public function index(Request $request, string $projectKey, int $id, string $section): JsonResponse
    {
        try {
            $output = $this->historySectionUseCase->handle(
                $this->resolveAdminProjectId(),
                $id,
                $section,
                (string) $request->query('q', ''),
                (string) $request->query('from', ''),
                (string) $request->query('to', ''),
            );
        } catch (InvalidArgumentException) {
            return response()->json(['message' => '不正なセクションです'], 422);
        }

        return response()->json([
            'html' => $output->html,
            'total' => $output->total,
            'section' => $output->section,
            'keyword' => $output->keyword,
            'from' => $output->from,
            'to' => $output->to,
        ]);
    }

    public function visit(string $projectKey, int $id, int $visitId): View
    {
        $output = $this->visitDetailUseCase->handle($this->resolveAdminProjectId(), $id, $visitId);

        return view('admin.member-visit-detail', [
            'member' => $output->member,
            'visit' => $output->visit,
        ]);
    }
}
