<?php

namespace App\Http\Controllers;

use App\Enums\StampGrantLimit;
use App\Services\UserService;
use App\UseCases\Stamp\Data\StampGrantInput;
use App\UseCases\Stamp\StampGrantUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StampGrantController extends Controller
{
    public function __construct(
        private UserService $userService,
        private StampGrantUseCase $stampGrantUseCase,
    ) {}

    public function __invoke(Request $request, int $stampCard): JsonResponse
    {
        $result = $this->stampGrantUseCase->handle(new StampGrantInput(
            $this->userService->getOrCreateUser($request),
            AdminContext::requireCurrentProjectId(),
            $stampCard,
            trim((string) $request->query('nonce', '')),
            (int) $request->query('amount', 1),
            (string) $request->query('limit', StampGrantLimit::PER_MEMBER),
            $this->queryExpiresAt($request),
            (int) $request->query('quota', 1),
        ));

        $payload = ['error' => $result->message];
        if ($result->isSuccess) {
            $payload = $result->successPayload();
        }

        return response()->json($payload, $result->status);
    }

    private function queryExpiresAt(Request $request): ?int
    {
        $expires = (int) $request->query('expires', 0);

        return $expires > 0 ? $expires : null;
    }
}
