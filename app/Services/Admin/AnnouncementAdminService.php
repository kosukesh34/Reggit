<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class AnnouncementAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Announcement::orderByDesc('published_at')->orderByDesc('id')->paginate($perPage);
    }

    public function create(int $projectId, array $data): Announcement
    {
        $data['project_id'] = $projectId;

        return Announcement::create($data);
    }

    public function findOrFail(int $id): Announcement
    {
        return Announcement::findOrFail($id);
    }

    public function update(int $id, array $data): Announcement
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update($data);

        return $announcement;
    }

    public function delete(int $id): void
    {
        Announcement::whereKey($id)->delete();
    }

    public function saveAnnouncementImage(UploadedFile $file, int $projectId): string
    {
        $path = $file->store('announcements/'.(string) $projectId, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store announcement image');
        }

        return asset('storage/'.$path);
    }
}
