<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Http\Support\QueueMeInputFactory;
use App\Services\UserService;
use App\Support\QueueSessionStore;
use App\UseCases\Queue\QueueMeUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class QueueMeController extends Controller
{
    public function __construct(
        private QueueMeUseCase $meUseCase,
        private UserService $userService,
        private QueueMeInputFactory $meInputFactory,
        private QueueSessionStore $sessionStore,
    ) {}

    public function __invoke(Request $request)
    {
        try {
            $result = $this->meUseCase->handle(
                $this->meInputFactory->fromRequest($request, $this->userService->getOrCreateUser($request))
            );
            $this->sessionStore->applyMeOutput($request, $result);

            return response()->json($result->toArray());
        } catch (\Exception $e) {
            LogHelper::error('[Queue] me failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => '取得に失敗しました'], 500);
        }
    }
}
