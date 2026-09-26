<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AnnouncementDeliveryStoreRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Announcement\AdminAnnouncementAudiencePreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeliveryFormUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementStoreDeliveryUseCase;
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
        $members = $this->searchMembersUseCase->handle(
            $this->resolveAdminProjectId(),
            (string) $request->query('q', ''),
        );

        return response()->json(['data' => $members]);
    }

    public function store(AnnouncementDeliveryStoreRequest $request): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        $imageFile = $request->file('image_file');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_url'] = $this->publicImageStorage->store(
                $imageFile,
                $projectId,
                PublicImageStorageService::DIR_ANNOUNCEMENT,
            );
        }
        unset($validated['image_file']);

        $output = $this->storeDeliveryUseCase->handle($projectId, $validated);

        if ($output->hasValidationError) {
            return back()
                ->withErrors([$output->validationField => $output->message])
                ->withInput();
        }

        return redirect()->route('admin.announcements', $this->buildAdminRouteParams())
            ->with('success', $output->message);
    }
}
