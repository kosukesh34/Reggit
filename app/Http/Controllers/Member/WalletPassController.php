<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\UseCases\Member\WalletPass\Data\WalletPassDownloadInput;
use App\UseCases\Member\WalletPass\WalletPassDownloadUseCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apple Wallet 用パス（.pkpass）をダウンロード。
 * 会員証画面・直接URL・?token=xxx のワンタイムトークンで同じ URL から .pkpass を返す。
 */
class WalletPassController extends Controller
{
    public function __construct(
        private WalletPassDownloadUseCase $downloadUseCase,
        private UserService $userService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->query('token');
        $user = null;
        if ($token === null || $token === '') {
            $user = $this->userService->getOrCreateUser($request);
        }

        $input = new WalletPassDownloadInput(
            token: $token !== null && $token !== '' ? $token : null,
            user: $user,
        );
        $result = $this->downloadUseCase->handle($input);

        if ($result->isAvailable() === false) {
            return response(
                "Wallet pass is not available.\n\n" . implode("\n", $result->unavailableReasons),
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $filename = 'membership.pkpass';
        return response($result->content, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($result->content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
