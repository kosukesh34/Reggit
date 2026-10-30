<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\UseCases\Stamp\StampHistoryUseCase;
use App\UseCases\Stamp\StampIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * スタンプ一覧 Web API。責務は UseCase に委譲する。
 */
class StampController extends Controller
{
    public function __construct(
        private StampIndexUseCase $stampIndexUseCase,
        private UserService $userService,
        private StampHistoryUseCase $stampHistoryUseCase,
    ) {}

    public function index(Request $request)
    {
        try {
            $output = $this->stampIndexUseCase->handle($this->userService->getOrCreateUser($request));

            if ($output->requiresMembership) {
                return response()->json([
                    'stamp_cards' => [],
                    'message' => '会員登録が必要です',
                    'default_image_url' => $output->defaultImageUrl,
                ]);
            }

            return response()->json([
                'stamp_cards' => $output->stampCardsToArray(),
                'default_image_url' => $output->defaultImageUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Stamps index error: '.$e->getMessage());

            return response()->json(['error' => 'スタンプの取得に失敗しました'], 500);
        }
    }

    public function history(Request $request, int $id)
    {
        try {
            $history = $this->stampHistoryUseCase->handle($this->userService->getOrCreateUser($request), $id);
            if ($history === null) {
                return response()->json(['error' => '会員登録が必要です'], 404);
            }

            return response()->json(['history' => $history]);
        } catch (\Exception $e) {
            Log::error('Stamps history error: '.$e->getMessage());

            return response()->json(['error' => 'スタンプ履歴の取得に失敗しました'], 500);
        }
    }
}
