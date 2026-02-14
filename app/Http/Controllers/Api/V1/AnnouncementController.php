<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use App\Util\LogHelper;

class AnnouncementController extends BaseApiController
{
    public function __construct(private AnnouncementIndexUseCase $indexUseCase)
    {
    }

    public function index(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle();

            return $this->success(['announcements' => $result->announcements]);
        } catch (\Exception $e) {
            LogHelper::error('[Announcement] index failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);
            return $this->error('お知らせの取得に失敗しました', 500);
        }
    }
}
