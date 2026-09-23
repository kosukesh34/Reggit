<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\StampGrantCode;
use App\Services\UserService;
use App\UseCases\Stamp\Data\StampGrantByCodeInput;
use App\UseCases\Stamp\StampGrantByCodeUseCase;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StampGrantCodeController extends Controller
{
    public function __construct(
        private UserService $userService,
        private StampGrantByCodeUseCase $stampGrantByCodeUseCase,
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        $code = StampGrantCode::withoutGlobalScope(ProjectScope::class)->where('token', $token)->first();
        if ($code === null) {
            return response()->json(['error' => '付与QRが見つかりません'], 404);
        }
        $project = Project::query()->find($code->project_id);
        if ($project === null) {
            return response()->json(['error' => '付与QRが見つかりません'], 404);
        }
        AdminContext::setCurrentProject($project);

        $result = $this->stampGrantByCodeUseCase->handle(new StampGrantByCodeInput(
            $this->userService->getOrCreateUser($request),
            $token,
        ));

        $payload = ['error' => $result->message];
        if ($result->isSuccess) {
            $payload = $result->successPayload();
        }

        return response()->json($payload, $result->status);
    }
}
