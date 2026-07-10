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
        private UserService $userService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $token = $request->query('token');
        $user = null;
        if ($token === null || $token === '') {
            $user = $this->userService->getOrCreateUser($request);
        }

        $result = $this->downloadUseCase->handle(new WalletPassDownloadInput(
            token: $token !== null && $token !== '' ? $token : null,
            user: $user,
        ));

        if ($result->isAvailable() === false) {
            $message = "Wallet に追加できませんでした。\n\n".implode("\n", $result->unavailableReasons);

            return response(
                $message,
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        return response($result->content, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="membership.pkpass"',
            'Content-Length' => (string) strlen((string) $result->content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
