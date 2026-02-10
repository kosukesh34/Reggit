<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Announcement;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $announcements = Announcement::where('project_id', $projectId)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })
                ->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->take(10)
                ->get(['id', 'title', 'body', 'published_at', 'is_pinned']);

            return $this->success(['announcements' => $announcements]);
        } catch (\Exception $e) {
            Log::error('Announcements fetch error: ' . $e->getMessage());
            return $this->error('お知らせの取得に失敗しました', 500);
        }
    }
}
