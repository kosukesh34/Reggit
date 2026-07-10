<?php

namespace App\Http\Controllers\Api\V1\Queue;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueStoreController extends BaseApiController
{
    public function __construct(
        private QueueStoreUseCase $storeUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ]);

        try {
            $user = $this->userService->getOrCreateUser($request);
            $dto = $this->storeUseCase->handle(new QueueStoreInput(
                $user,
                $validated['party_size'],
                $validated['guest_name'] ?? null,
                $validated['guest_phone'] ?? null,
                QueueRequestContext::lineUserId($request) ?: null,
            ));

            return $this->success([
                'queue_entry' => $dto->toArray(),
                'updated_at' => now()->toIso8601String(),
            ], '順番待ちに登録しました', 201);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] store failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return $this->error('順番待ちの登録に失敗しました', 500);
        }
    }
}
