<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\UserService;
use App\UseCases\Announcement\AnnouncementCancelledIdsUseCase;
use App\UseCases\Announcement\AnnouncementIndexUseCase;
use App\UseCases\Announcement\AnnouncementMarkReadUseCase;
use App\UseCases\Announcement\Data\AnnouncementMarkReadInput;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends BaseApiController
{
    public function __construct(
        private AnnouncementIndexUseCase $indexUseCase,
        private AnnouncementMarkReadUseCase $markReadUseCase,
        private AnnouncementCancelledIdsUseCase $cancelledIdsUseCase,
        private UserService $userService,
    ) {}

    public function index(Request $request)
    {
        try {
            $result = $this->indexUseCase->handle($this->userService->findUser($request));

            return $this->success(['announcements' => $result->announcements]);
        } catch (\Exception $e) {
            LogHelper::error('[Announcement] index failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('お知らせの取得に失敗しました', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markRead(Request $request)
    {
        $id = (int) $request->route('id');
        try {
            $result = $this->markReadUseCase->handle(new AnnouncementMarkReadInput(
                $this->userService->getOrCreateUser($request),
                $id
            ));

            return $this->success($result, '既読にしました');
        } catch (ValidationException $e) {
            return $this->error('対象のお知らせが見つかりません', Response::HTTP_NOT_FOUND, [], $e->errors());
        } catch (\Exception $e) {
            LogHelper::error('[Announcement] markRead failed', [
                'announcement_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('既読更新に失敗しました', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelledIds()
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $payload = $this->cancelledIdsUseCase->handleByProjectId((int) $projectId);

        return $this->success($payload);
    }
}
