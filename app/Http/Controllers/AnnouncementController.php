<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\UseCases\Announcement\AnnouncementMarkReadUseCase;
use App\UseCases\Announcement\Data\AnnouncementIndexInput;
use App\UseCases\Announcement\Data\AnnouncementMarkReadInput;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnouncementController extends Controller
{
    public function __construct(
        private AnnouncementIndexUseCase $indexUseCase,
        private AnnouncementMarkReadUseCase $markReadUseCase,
        private UserService $userService,
    ) {}

    public function index(Request $request)
    {
        $user = $this->userService->findUser($request);
        $result = $this->indexUseCase->handle(new AnnouncementIndexInput($user));

        return response()->json(['announcements' => $result->announcements]);
    }

    public function markRead(Request $request, int $id)
    {
        try {
            $user = $this->userService->getOrCreateUser($request);
            $result = $this->markReadUseCase->handle(new AnnouncementMarkReadInput($user, $id));

            return response()->json(['success' => true, 'data' => $result]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '対象のお知らせが見つかりません',
                'errors' => $e->errors(),
            ], 404);
        }
    }
}
