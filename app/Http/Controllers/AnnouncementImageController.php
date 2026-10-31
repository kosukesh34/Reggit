<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Announcement\AnnouncementPrivateImageUseCase;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementImageController extends Controller
{
    public function __invoke(Request $request, int $id, AnnouncementPrivateImageUseCase $images, UserService $users)
    {
        $announcement = $images->find(AdminContext::requireCurrentProjectId(), $id, $request->user('admin'), fn () => $users->findUser($request));
        abort_unless($announcement->private_image_path !== null && Storage::disk('local')->exists($announcement->private_image_path), 404);

        return Storage::disk('local')->response($announcement->private_image_path, null, ['Cache-Control' => 'private, no-store']);
    }
}
