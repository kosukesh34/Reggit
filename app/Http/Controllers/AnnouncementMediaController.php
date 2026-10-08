<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementMediaController extends Controller
{
    public function __invoke(Request $request, int $id, int $index)
    {
        abort_unless($request->hasValidSignature(), 403);
        $announcement = Announcement::withoutGlobalScopes()->findOrFail($id);
        $paths = $announcement->image_paths ?: array_filter([$announcement->private_image_path]);
        $path = $paths[$index] ?? null;
        abort_unless($path && hash_equals(hash('sha256', $path), (string) $request->query('version')), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
    }
}
