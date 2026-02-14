<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Util\AdminContext;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
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

        return response()->json(['announcements' => $announcements]);
    }
}
