<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AnnouncementVisibility;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementImageController extends Controller
{
    public function __invoke(Request $request, int $id, AnnouncementVisibility $visibility, UserService $users)
    {
        $query = Announcement::where('project_id', AdminContext::requireCurrentProjectId());
        $admin = $request->user('admin');
        if ($admin === null || ! $admin->canAccessProject(AdminContext::requireCurrentProjectId())) {
            $query = $visibility->apply($query, $users->findUser($request));
            $query->whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
        } elseif (! $admin->isSystemAdmin() && ! $admin->isProjectAdmin()) {
            $allowed = $admin->stores()->where('stores.project_id', AdminContext::requireCurrentProjectId())->pluck('stores.id')->all();
            $query->where(function ($q) use ($allowed) {
                $q->whereRaw('1 = 0');
                foreach ($allowed as $storeId) {
                    $q->orWhereJsonContains('store_ids', (int) $storeId);
                }
            });
        }
        $announcement = $query->findOrFail($id);
        abort_unless($announcement->private_image_path !== null && Storage::disk('local')->exists($announcement->private_image_path), 404);

        return Storage::disk('local')->response($announcement->private_image_path, null, ['Cache-Control' => 'private, no-store']);
    }
}
