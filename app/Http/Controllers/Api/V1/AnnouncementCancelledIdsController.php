<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\UseCases\Announcement\AnnouncementCancelledIdsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 削除済みお知らせの通知取消し ID 一覧。
 * ネイティブ起動時掃除用（認証不要・project_key クエリ）。
 */
class AnnouncementCancelledIdsController extends Controller
{
    public function __construct(
        private AnnouncementCancelledIdsUseCase $cancelledIdsUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $projectKey = (string) $request->query('project_key', 'default');
        $payload = $this->cancelledIdsUseCase->handleByProjectKey($projectKey);

        return response()->json($payload);
    }
}
