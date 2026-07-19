<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueStoreController extends Controller
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
            $result = $this->storeUseCase->handle(new QueueStoreInput(
                $user,
                $validated['party_size'],
                $validated['guest_name'] ?? null,
                $validated['guest_phone'] ?? null,
                QueueRequestContext::lineUserId($request) ?: null,
            ));

            if ($result->requiresMembership) {
                return response()->json([
                    'error' => '会員登録が必要です',
                    'requires_membership' => true,
                ], 403);
            }

            return response()->json([
                'message' => '順番待ちに登録しました',
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $result->entry?->toArray(),
            ], 201);
        } catch (\Exception $e) {
            LogHelper::error('[Queue] store failed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? 1,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '順番待ちの登録に失敗しました'], 500);
        }
    }
}
