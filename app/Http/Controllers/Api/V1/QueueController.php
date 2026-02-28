<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Queue\QueueMeUseCase;
use App\UseCases\Queue\QueueStoreUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 順番待ち API（レガシー互換）。責務は UseCase に委譲する。
 */
class QueueController extends BaseApiController
{
    private QueueStoreUseCase $storeUseCase;
    private QueueMeUseCase $meUseCase;

    public function __construct(
        QueueStoreUseCase $storeUseCase,
        QueueMeUseCase $meUseCase
    ) {
        $this->storeUseCase = $storeUseCase;
        $this->meUseCase = $meUseCase;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ]);

        try {
            $dto = $this->storeUseCase->handle(
                $request,
                (int) $validated['party_size'],
                isset($validated['guest_name']) ? (string) $validated['guest_name'] : null,
                isset($validated['guest_phone']) ? (string) $validated['guest_phone'] : null
            );

            return $this->success([
                'queue_entry' => $dto->toArray(),
                'updated_at' => now()->toIso8601String(),
            ], '順番待ちに登録しました', 201);
        } catch (\Exception $e) {
            Log::error('Queue store error: ' . $e->getMessage());

            return $this->error('順番待ちの登録に失敗しました', 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $output = $this->meUseCase->handle($request);

            if ($output->queueEntry === null) {
                return $this->success([
                    'queue_entry' => null,
                    'message' => '待ち番号はありません',
                    'waiting_count' => $output->waitingCount,
                    'estimated_wait_minutes' => $output->estimatedWaitMinutes,
                    'updated_at' => now()->toIso8601String(),
                ]);
            }

            return $this->success([
                'queue_entry' => $output->queueEntry->toArray(),
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Queue me error: ' . $e->getMessage());

            return $this->error('取得に失敗しました', 500);
        }
    }
}
