<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueInput;
use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueOutput;
use App\UseCases\Member\WalletPass\WalletPassUrlIssueUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safari で開く用のワンタイム URL を返す（LINE アプリ内では .pkpass が開けないため）。
 */
class WalletPassUrlController extends Controller
{
    public function __construct(
        private WalletPassUrlIssueUseCase $urlIssueUseCase,
        private UserService $userService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $project = AdminContext::getCurrentProject();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが特定できません'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->getOrCreateUser($request);
        $output = $this->urlIssueUseCase->handle(new WalletPassUrlIssueInput(
            $user,
            $project->id,
            $project->project_key,
        ));

        return $this->outputToResponse($user, $project->project_key, $output);
    }

    private function outputToResponse(User $user, string $projectKey, WalletPassUrlIssueOutput $output): Response
    {
        if ($output->success) {
            LogHelper::info('[WalletPass] url issued', [
                'user_id' => $user->id,
                'line_user_tail' => is_string($user->line_user_id) ? substr($user->line_user_id, -4) : null,
                'project_key' => $projectKey,
            ]);

            return response()->json(['url' => $output->url]);
        }

        if ($output->errorType === WalletPassUrlIssueOutput::ERROR_UNAVAILABLE) {
            LogHelper::warning('[WalletPass] url issue rejected: unavailable', [
                'reasons' => $output->reasons,
            ]);

            return response()->json(['error' => $output->errorMessage], Response::HTTP_FORBIDDEN);
        }

        LogHelper::warning('[WalletPass] url issue rejected: not member', [
            'user_id' => $user->id,
            'line_user_tail' => is_string($user->line_user_id) ? substr($user->line_user_id, -4) : null,
        ]);

        return response()->json(['error' => $output->errorMessage], Response::HTTP_FORBIDDEN);
    }
}
