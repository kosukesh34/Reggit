<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueStoreController extends Controller
{
    public function __construct(
        private QueueStoreUseCase $storeUseCase
    ) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'party_size' => 'required|integer|min:1|max:20',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:50',
        ]);

        try {
            $dto = $this->storeUseCase->handle(
                $request,
                $validated['party_size'],
                $validated['guest_name'] ?? null,
                $validated['guest_phone'] ?? null
            );

            return response()->json([
                'message' => '順番待ちに登録しました',
                'updated_at' => now()->toIso8601String(),
                'queue_entry' => $dto->toArray(),
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
