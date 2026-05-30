<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlInput;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlOutput;
use App\UseCases\Member\GoogleWallet\GetGoogleWalletUrlUseCase;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Google Wallet に追加するための URL（JWT 付き）を返す。
 * セッション（会員）またはワンタイム token で認証。責務は UseCase に委譲する。
 */
class GoogleWalletUrlController extends Controller
{
    public function __construct(
        private readonly GetGoogleWalletUrlUseCase $getGoogleWalletUrlUseCase,
        private readonly UserService $userService
    ) {}

    public function __invoke(Request $request): Response
    {
        $input = $this->buildInput($request);
        $output = $this->getGoogleWalletUrlUseCase->handle($input);

        return $this->outputToResponse($output);
    }

    private function buildInput(Request $request): GetGoogleWalletUrlInput
    {
        $token = $request->query('token');
        $projectId = AdminContext::getCurrentProjectId();
        $project = AdminContext::getCurrentProject();
        $projectKey = $project !== null ? $project->project_key : config('app.default_project_key', 'default');
        $projectName = $project !== null ? $project->name : null;

        $user = null;
        if (StrHelper::isEmptyStringOrNull($token)) {
            $user = $this->userService->getOrCreateUser($request);
        }

        return new GetGoogleWalletUrlInput(
            token: $token,
            user: $user,
            projectId: $projectId,
            projectKey: $projectKey,
            projectName: $projectName
        );
    }

    private function outputToResponse(GetGoogleWalletUrlOutput $output): Response
    {
        if ($output->success) {
            return response()->json(['url' => $output->url]);
        }

        if ($output->errorType === GetGoogleWalletUrlOutput::ERROR_UNAVAILABLE) {
            return response()->json([
                'error' => $output->errorMessage,
                'reasons' => $output->reasons,
            ], Response::HTTP_FORBIDDEN);
        }

        if ($output->errorType === GetGoogleWalletUrlOutput::ERROR_NOT_MEMBER) {
            return response()->json(['error' => $output->errorMessage], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['error' => $output->errorMessage], Response::HTTP_BAD_REQUEST);
    }
}
