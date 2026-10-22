<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Shop\LineLoginClient;
use App\Support\ShopIntendedUrl;
use App\Support\ShopMemberSession;
use App\UseCases\ShopAuth\LoginWithLineUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class ShopLineAuthController extends Controller
{
    public function __construct(
        private LineLoginClient $lineLoginClient,
        private LoginWithLineUseCase $loginWithLine,
        private ShopMemberSession $session,
    ) {}

    public function start(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('shop_line_oauth_state', $state);
        $redirectUri = $this->callbackUrl($request);

        return redirect()->away($this->lineLoginClient->authorizeUrl($redirectUri, $state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expected = (string) $request->session()->pull('shop_line_oauth_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || ! hash_equals($expected, $state)) {
            return $this->loginRedirect($request)->withErrors(['line' => 'LINEログインの検証に失敗しました。']);
        }
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return $this->loginRedirect($request)->withErrors(['line' => 'LINEログインがキャンセルされました。']);
        }
        try {
            $this->session->login($this->loginWithLine->handle($code, $this->callbackUrl($request)), $request);
        } catch (RuntimeException $exception) {
            return $this->loginRedirect($request)->withErrors(['line' => $exception->getMessage()]);
        }

        return redirect()->to(ShopIntendedUrl::pull($request, $this->shopUrl($request)));
    }

    private function callbackUrl(Request $request): string
    {
        $projectKey = $request->route('project_key');
        if (is_string($projectKey) && $projectKey !== '') {
            return route('shop.line.callback', ['project_key' => $projectKey]);
        }

        return route('shop.domain.line.callback');
    }

    private function loginRedirect(Request $request): RedirectResponse
    {
        $projectKey = $request->route('project_key');
        if (is_string($projectKey) && $projectKey !== '') {
            return redirect()->route('shop.login', ['project_key' => $projectKey]);
        }

        return redirect()->route('shop.domain.login');
    }

    private function shopUrl(Request $request): string
    {
        $projectKey = $request->route('project_key');
        if (is_string($projectKey) && $projectKey !== '') {
            return route('shop.show', ['project_key' => $projectKey]);
        }

        return route('shop.domain.show');
    }
}
