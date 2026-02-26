<?php

namespace App\Http\Controllers;

use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\UseCases\Announcement\AnnouncementMarkReadUseCase;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(
        private AnnouncementIndexUseCase $indexUseCase,
        private AnnouncementMarkReadUseCase $markReadUseCase
    ) {
    }

    public function index(Request $request)
    {
        $result = $this->indexUseCase->handle($request);
        return response()->json(['announcements' => $result->announcements]);
    }

    public function markRead(Request $request, int $id)
    {
        try {
            $result = $this->markReadUseCase->handle($request, $id);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '対象のお知らせが見つかりません',
                'errors' => $e->errors(),
            ], 404);
        }
    }
}
