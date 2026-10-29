<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementUpdateRequest;
use App\Services\Admin\AnnouncementImageStorage;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementEditUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementIndexUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementReadsUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementUpdateUseCase;
use App\UseCases\Admin\Announcement\AdminLineDeliveryFailureIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class AnnouncementController extends AdminBaseController
{
    public function __construct(
        private AdminAnnouncementIndexUseCase $indexUseCase,
        private AdminAnnouncementEditUseCase $editUseCase,
        private AdminAnnouncementUpdateUseCase $updateUseCase,
        private AdminAnnouncementDeleteUseCase $deleteUseCase,
        private PublicImageStorageService $publicImageStorage,
        private AdminAnnouncementReadsUseCase $readsUseCase,
    ) {}

    public function announcements(Request $request)
    {
        $output = $this->indexUseCase->handle([
            'delivery_type' => $request->query('delivery_type'),
            'keyword' => $request->query('keyword'),
            'published_from' => $request->query('published_from'),
            'published_to' => $request->query('published_to'),
        ]);
        $output->announcements->appends($request->query());

        return view('admin.announcements.index', ['view' => $output]);
    }

    public function createAnnouncement()
    {
        return redirect()->route('admin.announcements', $this->buildAdminRouteParams());
    }

    public function editAnnouncement(string $projectKey, int $id)
    {
        $announcement = $this->editUseCase->handle($id);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(AnnouncementUpdateRequest $request, string $projectKey, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $this->editUseCase->handle($id);

        $imageFile = $request->file('image_file');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_paths'] = null;
            $announcement = $this->editUseCase->handle($id);
            if ($announcement->visibility === 'recipients') {
                $validated['private_image_path'] = $imageFile->store('announcement-private/'.$projectId, 'local');
                $validated['image_url'] = null;
            } else {
                $validated['image_url'] = $this->publicImageStorage->store($imageFile, $projectId, PublicImageStorageService::DIR_ANNOUNCEMENT);
            }
        }
        if ($request->hasFile('image_files')) {
            $validated['image_paths'] = array_map(
                fn (UploadedFile $file) => app(AnnouncementImageStorage::class)->store($file, $projectId),
                array_values($request->file('image_files')),
            );
            $validated['private_image_path'] = null;
            $validated['image_url'] = null;
        } elseif ($request->boolean('clear_images')) {
            $validated['image_paths'] = null;
            $validated['private_image_path'] = null;
            $validated['image_url'] = null;
        }
        unset($validated['image_file'], $validated['image_files'], $validated['clear_images']);

        $this->updateUseCase->handle($id, $validated);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを更新しました');
    }

    public function deleteAnnouncement(string $projectKey, int $id)
    {
        $this->deleteUseCase->handle($id);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', 'お知らせを削除しました');
    }

    public function announcementReads(string $projectKey, int $id)
    {
        $output = $this->readsUseCase->handle($id);

        return view('admin.announcements.reads', ['view' => $output]);
    }

    public function lineFailures(Request $request, AdminLineDeliveryFailureIndexUseCase $useCase)
    {
        return view('admin.announcements.line-failures', $useCase->handle(
            $this->resolveAdminProjectId(),
            $request->query('reason'),
        ));
    }
}
