<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\MemberService;
use App\Services\UserService;
use App\Services\WalletPassService;
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
        private MemberService $memberService,
        private WalletPassService $walletPassService
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($this->walletPassService->isAvailable() !== true) {
            LogHelper::warning('[WalletPass] url issue rejected: unavailable', [
                'reasons' => $this->walletPassService->getUnavailabilityReasons(),
            ]);

            return response()->json(['error' => 'Apple Wallet は現在ご利用いただけません'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->userService->getOrCreateUser($request);
        $memberData = $this->memberService->getMemberData($user);
        if (! isset($memberData['is_member']) || $memberData['is_member'] === false) {
            LogHelper::warning('[WalletPass] url issue rejected: not member', [
                'user_id' => $user->id,
                'line_user_tail' => is_string($user->line_user_id) ? substr($user->line_user_id, -4) : null,
            ]);

            return response()->json(['error' => '会員登録が必要です'], Response::HTTP_FORBIDDEN);
        }

        $project = AdminContext::getCurrentProject();
        if ($project === null) {
            return response()->json(['error' => 'プロジェクトが特定できません'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->urlIssueUseCase->handle(
            $user->id,
            $project->id,
            $project->project_key
        );

        LogHelper::info('[WalletPass] url issued', [
            'user_id' => $user->id,
            'line_user_tail' => is_string($user->line_user_id) ? substr($user->line_user_id, -4) : null,
            'project_key' => $project->project_key,
        ]);

        return response()->json(['url' => $result->url]);
    }
}
