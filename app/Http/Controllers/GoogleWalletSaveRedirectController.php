<?php

namespace App\Http\Controllers;

use App\UseCases\Member\GoogleWallet\GoogleWalletSaveUrlIssuer;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * LIFF 向け短い URL から pay.google.com の Save URL へリダイレクトする。
 */
class GoogleWalletSaveRedirectController extends Controller
{
    public function __construct(
        private readonly GoogleWalletSaveUrlIssuer $saveUrlIssuer
    ) {}

    public function __invoke(Request $request, string $token): View|Response
    {
        $cached = $this->saveUrlIssuer->resolveCached($token);
        if ($cached === null) {
            LogHelper::warning('[GoogleWallet] save redirect cache miss', [
                'token_tail' => substr($token, -6),
            ]);

            return response(
                "Google Wallet のリンクの有効期限が切れました。\n会員証画面から「Google Walletに追加」をもう一度タップしてください。",
                Response::HTTP_GONE,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId !== null && $cached['project_id'] !== null && (int) $cached['project_id'] !== (int) $projectId) {
            LogHelper::warning('[GoogleWallet] save redirect project mismatch', [
                'expected' => $projectId,
                'cached' => $cached['project_id'],
            ]);

            return response(
                'プロジェクトの設定が一致しません。会員証画面から再度お試しください。',
                Response::HTTP_FORBIDDEN,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        LogHelper::info('[GoogleWallet] save redirect', [
            'token_tail' => substr($token, -6),
            'target_host' => parse_url($cached['url'], PHP_URL_HOST),
            'target_url_length' => strlen($cached['url']),
        ]);

        // pay.google.com の Save URL（JWT 数千文字）を Location ヘッダーに載せると nginx 502 になるため HTML 経由で遷移する
        return view('google-wallet-save-redirect', [
            'targetUrl' => $cached['url'],
        ]);
    }
}
