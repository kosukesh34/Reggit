<?php

namespace App\Http\Controllers;

use App\Support\QueueLiffUrlBuilder;
use App\UseCases\Member\WalletPass\WalletPassUrlIssueUseCase;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Apple / Google Wallet 追加用の中間ページ。
 * 外部ブラウザで開き、「Walletに追加」と「アプリに戻る」の両方を表示する。
 */
class WalletAddPageController extends Controller
{
    public function __construct(
        private WalletPassUrlIssueUseCase $walletPassUrlIssueUseCase
    ) {}

    public function __invoke(Request $request): View
    {
        $walletUrl = $request->query('wallet_url');
        $passToken = $request->query('pass_token');
        $projectKey = $request->query('project_key', 'default');
        $walletUrlFromPassToken = false;
        if ((! is_string($walletUrl) || trim($walletUrl) === '') && is_string($passToken) && trim($passToken) !== '') {
            $walletUrl = $this->walletPassUrlIssueUseCase->buildPassDownloadUrl(
                trim($passToken),
                is_string($projectKey) && trim($projectKey) !== '' ? trim($projectKey) : 'default'
            );
            $walletUrlFromPassToken = is_string($walletUrl) && trim($walletUrl) !== '';
        }
        $returnUrl = $request->query('return_url');
        $returnTarget = $request->query('return_target', 'web');
        $type = $request->query('type', 'apple');

        $type = strtolower((string) $type) === 'google' ? 'google' : 'apple';
        $typeLabel = $type === 'google' ? 'Google Wallet' : 'Apple Wallet';
        $addButtonLabel = $type === 'google' ? 'Google Wallet に追加' : 'Apple Wallet に追加';

        $returnTarget = in_array(strtolower((string) $returnTarget), ['line', 'app', 'web'], true)
            ? strtolower((string) $returnTarget) : 'web';
        $returnBackLabel = $returnTarget === 'line' ? 'LINEに戻る' : ($returnTarget === 'app' ? 'アプリに戻る' : 'アプリに戻る');

        if (! $walletUrlFromPassToken) {
            $walletUrl = $this->sanitizeWalletUrl(is_string($walletUrl) ? $walletUrl : null);
        }
        $returnUrl = $this->resolveReturnUrl(
            $this->sanitizeReturnUrl($returnUrl),
            $returnTarget,
            is_string($projectKey) && trim($projectKey) !== '' ? trim($projectKey) : 'default'
        );

        return view('wallet-add', [
            'walletUrl' => $walletUrl,
            'returnUrl' => $returnUrl,
            'typeLabel' => $typeLabel,
            'addButtonLabel' => $addButtonLabel,
            'returnBackLabel' => $returnBackLabel,
        ]);
    }

    private function sanitizeWalletUrl(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }
        $url = trim($url);
        if (! Str::startsWith($url, ['https://', 'http://'])) {
            return null;
        }
        try {
            $parsed = parse_url($url);
            $host = strtolower((string) ($parsed['host'] ?? ''));
            if ($host === '') {
                return null;
            }
            if ($this->isAllowedWalletHost($host)) {
                return $url;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function isAllowedWalletHost(string $host): bool
    {
        if (str_ends_with($host, '.ngrok-free.app') || str_ends_with($host, '.ngrok.io') || str_ends_with($host, '.ngrok-free.dev')) {
            return true;
        }

        $allowedHosts = array_filter([
            'pay.google.com',
            parse_url((string) config('app.url', ''), PHP_URL_HOST) ?: null,
            request()->getHost(),
            $this->forwardedHost(),
        ]);

        foreach ($allowedHosts as $allowed) {
            $allowed = strtolower((string) $allowed);
            if ($allowed === '') {
                continue;
            }
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    private function forwardedHost(): ?string
    {
        $forwarded = request()->header('X-Forwarded-Host') ?? request()->server('HTTP_X_FORWARDED_HOST');
        if (! is_string($forwarded) || trim($forwarded) === '') {
            return null;
        }

        return trim(explode(',', $forwarded)[0]);
    }

    private function sanitizeReturnUrl(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }
        $url = trim($url);
        if (! Str::startsWith($url, ['https://', 'http://', 'mobileorderapp://'])) {
            return null;
        }
        try {
            $parsed = parse_url($url);
            $scheme = $parsed['scheme'] ?? '';
            if (strtolower($scheme) === 'mobileorderapp') {
                return $url;
            }
            $host = $parsed['host'] ?? '';
            $appHost = request()->getHost();
            $configHost = parse_url(config('app.url', ''), PHP_URL_HOST);
            $allowedHosts = array_filter([$appHost, $configHost, 'liff.line.me', 'line.me']);
            foreach ($allowedHosts as $allowed) {
                if ($allowed !== '' && (strtolower($host) === strtolower($allowed) || Str::endsWith(strtolower($host), '.'.strtolower($allowed)))) {
                    return $url;
                }
            }
            if (str_ends_with(strtolower($host), '.ngrok-free.dev')
                || str_ends_with(strtolower($host), '.ngrok-free.app')
                || str_ends_with(strtolower($host), '.ngrok.io')) {
                return $url;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function resolveReturnUrl(?string $returnUrl, string $returnTarget, string $projectKey): ?string
    {
        if ($returnTarget !== 'line') {
            return $returnUrl;
        }

        if (is_string($returnUrl) && str_contains(strtolower($returnUrl), 'liff.line.me')) {
            return $returnUrl;
        }

        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || $projectId === false) {
            $projectId = 1;
        }

        return $this->sanitizeReturnUrl(
            QueueLiffUrlBuilder::buildEntryUrl((int) $projectId, $projectKey)
        );
    }
}
