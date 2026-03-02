<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\UseCases\Announcement\AnnouncementMarkReadUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class AnnouncementController extends BaseApiController
{
    public function __construct(
        private AnnouncementIndexUseCase $indexUseCase,
        private AnnouncementMarkReadUseCase $markReadUseCase
    ) {}

    public function index(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($request);

            return $this->success(['announcements' => $result->announcements]);
        } catch (\Exception $e) {
            LogHelper::error('[Announcement] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('お知らせの取得に失敗しました', 500);
        }
    }

    public function markRead(Request $request, int $id)
    {
        try {
            $result = $this->markReadUseCase->handle($request, $id);

            return $this->success($result, '既読にしました');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('対象のお知らせが見つかりません', 404, [], $e->errors());
        } catch (\Exception $e) {
            LogHelper::error('[Announcement] markRead failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'announcement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('既読更新に失敗しました', 500);
        }
    }
}
