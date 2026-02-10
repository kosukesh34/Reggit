<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
