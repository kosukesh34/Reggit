<?php

namespace App\Http\Controllers;

use App\Http\Support\QueueMeInputFactory;
use App\Services\UserService;
use App\Support\QueueRequestContext;
use App\Support\QueueSessionStore;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueMeUseCase;
use App\UseCases\Queue\QueueStoreUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 順番待ち Web API。責務は UseCase に委譲する。
 */
class QueueController extends Controller
{
    public function __construct(
        private QueueStoreUseCase $storeUseCase,
        private QueueMeUseCase $meUseCase,
        private UserService $userService,
        private QueueMeInputFactory $meInputFactory,
        private QueueSessionStore $sessionStore,
    ) {}

    public function store(Request $request)
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
                (int) $validated['party_size'],
                isset($validated['guest_name']) ? (string) $validated['guest_name'] : null,
                isset($validated['guest_phone']) ? (string) $validated['guest_phone'] : null,
                QueueRequestContext::lineUserId($request) ?: null,
            ));

            return response()->json([
                'message' => '順番待ちに登録しました',
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $dto->toArray(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Queue store error: '.$e->getMessage());

            return response()->json(['error' => '順番待ちの登録に失敗しました'], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $output = $this->meUseCase->handle(
                $this->meInputFactory->fromRequest($request, $this->userService->getOrCreateUser($request))
            );
            $this->sessionStore->applyMeOutput($request, $output);

            if ($output->queueEntry === null) {
                return response()->json([
                    'queue_entry' => null,
                    'message' => '待ち番号はありません',
                    'waiting_count' => $output->waitingCount,
                    'estimated_wait_minutes' => $output->estimatedWaitMinutes,
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            return response()->json([
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $output->queueEntry->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Queue me error: '.$e->getMessage());

            return response()->json(['error' => '取得に失敗しました'], 500);
        }
    }
}
