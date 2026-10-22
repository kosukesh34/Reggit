<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\ShopIntendedUrl;
use App\Support\ShopMemberSession;
use App\UseCases\Member\ClaimMemberTransferCodeUseCase;
use App\UseCases\ShopAuth\ConsumeShopMagicLinkUseCase;
use App\UseCases\ShopAuth\PrepareShopAppLoginActorUseCase;
use App\UseCases\ShopAuth\SendShopMagicLinkUseCase;
use App\Util\AdminContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use RuntimeException;

class ShopAuthController extends Controller
{
    public function __construct(
        private ShopMemberSession $session,
        private SendShopMagicLinkUseCase $sendMagicLink,
        private ConsumeShopMagicLinkUseCase $consumeMagicLink,
        private PrepareShopAppLoginActorUseCase $prepareShopAppLoginActor,
        private ClaimMemberTransferCodeUseCase $claimMemberTransferCode,
    ) {}

    public function loginForm(Request $request): View
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $projectKey = (string) ($request->route('project_key') ?? $project->project_key);
        $mode = (string) $request->query('mode', 'login');
        if ($mode !== 'register') {
            $mode = 'login';
        }
        ShopIntendedUrl::remember($request, (string) $request->query('intended', ''));

        return view('frontend.shop-login', [
            'projectKey' => $projectKey,
            'projectName' => (string) $project->name,
            'mode' => $mode,
            'lineStartUrl' => $this->namedRoute('shop.line.start', $request),
            'emailUrl' => $this->namedRoute('shop.email.send', $request),
            'appLoginUrl' => $this->namedRoute('shop.app.login', $request),
            'passkeyOptionsUrl' => $this->namedRoute('shop.passkey.login.options', $request),
            'passkeySubmitUrl' => $this->namedRoute('shop.passkey.login', $request),
            'shopUrl' => $this->namedRoute('shop.show', $request),
            'loginUrl' => $this->namedRoute('shop.login', $request),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->session->logout($request);

        return redirect()->to($this->namedRoute('shop.show', $request));
    }

    public function sendEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'mode' => ['nullable', 'in:login,register'],
        ]);
        $consumeName = $request->route('project_key') ? 'shop.email.consume' : 'shop.domain.email.consume';
        $params = $request->route('project_key')
            ? ['project_key' => (string) $request->route('project_key')]
            : [];
        $this->sendMagicLink->handle($validated['email'], $consumeName, $params);
        $isRegister = ($validated['mode'] ?? 'login') === 'register';

        return back()->with(
            'success',
            $isRegister
                ? '登録用のメールを送信しました。メール内のリンクを開いて登録を完了してください。'
                : 'ログイン用のメールを送信しました。メール内のリンクを開いてください。'
        );
    }

    public function consumeEmail(Request $request): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            abort(403, 'リンクの有効期限が切れているか、不正です。');
        }
        try {
            $this->session->login($this->consumeMagicLink->handle((string) $request->query('email', '')), $request);
        } catch (RuntimeException $exception) {
            return redirect()->to($this->namedRoute('shop.login', $request))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        return redirect()->to(ShopIntendedUrl::pull($request, $this->namedRoute('shop.show', $request)));
    }

    public function loginWithApp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            // コードが違うときに作ったゲストを残さないよう、ゲスト作成と引き継ぎを同じトランザクションにする
            $source = DB::transaction(fn () => $this->claimMemberTransferCode->handle(
                $this->prepareShopAppLoginActor->handle(),
                null,
                (string) $validated['code'],
                (int) $validated['member_id'],
            ));
            $this->session->login($source, $request);
        } catch (RuntimeException|InvalidArgumentException $exception) {
            return back()
                ->withErrors(['app' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->to(ShopIntendedUrl::pull($request, $this->namedRoute('shop.show', $request)));
    }

    private function namedRoute(string $base, Request $request): string
    {
        $projectKey = $request->route('project_key');
        if (is_string($projectKey) && $projectKey !== '') {
            return route($base, ['project_key' => $projectKey]);
        }
        $domainMap = [
            'shop.show' => 'shop.domain.show',
            'shop.login' => 'shop.domain.login',
            'shop.logout' => 'shop.domain.logout',
            'shop.line.start' => 'shop.domain.line.start',
            'shop.email.send' => 'shop.domain.email.send',
            'shop.app.login' => 'shop.domain.app.login',
            'shop.passkey.login.options' => 'shop.domain.passkey.login.options',
            'shop.passkey.login' => 'shop.domain.passkey.login',
        ];

        return route($domainMap[$base] ?? $base);
    }
}
