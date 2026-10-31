<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Services\Admin\AnnouncementImageStorage;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Announcement\AdminAnnouncementAudiencePreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeliveryFormUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreDeliveryUseCase;
use App\UseCases\Admin\Announcement\AdminMemberAudienceGroupUseCase;
use App\UseCases\Admin\Member\AdminMemberSearchByKeywordUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class AnnouncementDeliveryController extends AdminBaseController
{
    public function __construct(
        private AdminAnnouncementStoreDeliveryUseCase $storeDeliveryUseCase,
        private AdminAnnouncementDeliveryFormUseCase $deliveryFormUseCase,
        private AdminAnnouncementSegmentPreviewUseCase $segmentPreviewUseCase,
        private AdminAnnouncementAudiencePreviewUseCase $audiencePreviewUseCase,
        private AdminMemberSearchByKeywordUseCase $searchMembersUseCase,
        private PublicImageStorageService $publicImageStorage,
        private AdminMemberAudienceGroupUseCase $audienceGroupUseCase,
    ) {}

    public function create(): View
    {
        $form = $this->deliveryFormUseCase->handle($this->resolveAdminProjectId());

        return view('admin.announcements.delivery-create', ['view' => $form]);
    }

    public function segmentPreview(Request $request): JsonResponse
    {
        $count = $this->segmentPreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->query(),
        );

        return response()->json(['count' => $count]);
    }

    public function audiencePreview(Request $request): JsonResponse
    {
        return response()->json($this->audiencePreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->query(),
        ));
    }

    public function searchMembers(Request $request): JsonResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $numbers = trim((string) $request->query('numbers', ''));
        if ($numbers !== '') {
            return response()->json($this->searchMembersUseCase->findByNumberList($projectId, $numbers));
        }

        $members = $this->searchMembersUseCase->handle(
            $projectId,
            (string) $request->query('q', ''),
        );

        return response()->json(['data' => $members]);
    }

    public function store(AnnouncementDeliveryStoreRequest $request, AnnouncementImageStorage $images): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        $imageFile = $request->file('image_file');
        if ($imageFile instanceof UploadedFile) {
            $validated['private_image_path'] = $imageFile->store('announcement-private/'.$projectId, 'local');
            $validated['image_url'] = null;
        }
        if ($request->hasFile('image_files')) {
            $validated['image_paths'] = array_map(
                fn (UploadedFile $file) => $images->store($file, $projectId),
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

        $output = $this->storeDeliveryUseCase->handle($projectId, $validated);

        if ($output->hasValidationError) {
            return back()
                ->withErrors([$output->validationField => $output->message])
                ->withInput();
        }

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', $output->message);
    }

    public function storeAudienceGroup(Request $request): RedirectResponse
    {
        $group = $this->audienceGroupUseCase->store(
            $this->resolveAdminProjectId(),
            (string) $request->input('audience_group_name', ''),
            $request->all(),
        );

        return back()
            ->withInput()
            ->with('success', '配信グループ「'.$group->name.'」を保存しました');
    }

    public function destroyAudienceGroup(string $projectKey, int $id): RedirectResponse
    {
        $this->audienceGroupUseCase->delete($this->resolveAdminProjectId(), $id);

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', '配信グループを削除しました');
    }
}
