<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Apple / Google Wallet 追加用の中間ページ。
 * 外部ブラウザで開き、「Walletに追加」と「アプリに戻る」の両方を表示する。
 */
class WalletAddPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $walletUrl = $request->query('wallet_url');
        $returnUrl = $request->query('return_url');
        $returnTarget = $request->query('return_target', 'web');
        $type = $request->query('type', 'apple');

        $type = strtolower((string) $type) === 'google' ? 'google' : 'apple';
        $typeLabel = $type === 'google' ? 'Google Wallet' : 'Apple Wallet';
        $addButtonLabel = $type === 'google' ? 'Google Wallet に追加' : 'Apple Wallet に追加';

        $returnTarget = in_array(strtolower((string) $returnTarget), ['line', 'app', 'web'], true)
            ? strtolower((string) $returnTarget) : 'web';
        $returnBackLabel = $returnTarget === 'line' ? 'LINEに戻る' : ($returnTarget === 'app' ? 'アプリに戻る' : 'アプリに戻る');

        $walletUrl = $this->sanitizeWalletUrl($walletUrl);
        $returnUrl = $this->sanitizeReturnUrl($returnUrl);

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
            $host = $parsed['host'] ?? '';
            $allowedHosts = [
                'pay.google.com',
                parse_url(config('app.url', ''), PHP_URL_HOST) ?: request()->getHost(),
            ];
            $allowedHosts = array_filter($allowedHosts);
            foreach ($allowedHosts as $allowed) {
                if ($allowed !== '' && (strtolower($host) === strtolower($allowed) || Str::endsWith(strtolower($host), '.'.strtolower($allowed)))) {
                    return $url;
                }
            }
            if (request()->getHost() && (strtolower($host) === strtolower(request()->getHost()) || Str::endsWith(strtolower($host), '.'.strtolower(request()->getHost())))) {
                return $url;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
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
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
