<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Shop\MemberPasskeyService;
use App\Support\PasskeyOrigin;
use App\Support\ShopIntendedUrl;
use App\Support\ShopMemberSession;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;

class ShopPasskeyController extends Controller
{
    public function __construct(
        private MemberPasskeyService $passkeys,
        private ShopMemberSession $session,
    ) {}

    public function loginOptions(Request $request): JsonResponse
    {
        PasskeyOrigin::apply($request);
        $email = strtolower(trim((string) $request->query('email', '')));
        $user = null;
        if ($email !== '') {
            $user = User::query()
                ->where('project_id', AdminContext::requireCurrentProjectId())
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        return response()->json([
            'options' => $this->passkeys->loginOptions($user, $request),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credential = $this->credential($request);
            $user = $this->passkeys->verify($credential, $request);
            if ((int) $user->project_id !== AdminContext::requireCurrentProjectId()) {
                abort(403);
            }
            $this->session->login($user, $request);
        } catch (InvalidPasskeyException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'redirect' => ShopIntendedUrl::pull($request, $this->shopUrl($request)),
        ]);
    }

    public function registerOptions(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        return response()->json([
            'options' => $this->passkeys->registrationOptions($user, $request),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $name = trim((string) $request->input('name', 'パスキー'));
        try {
            $this->passkeys->store($user, $name, $this->credential($request), $request);
        } catch (InvalidPasskeyException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    private function requireUser(): User
    {
        $user = $this->session->current();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function credential(Request $request): PublicKeyCredential
    {
        $payload = $request->input('credential');
        if (! is_array($payload)) {
            abort(422, 'credential が必要です。');
        }

        return WebAuthn::fromJson(json_encode($payload, JSON_THROW_ON_ERROR), PublicKeyCredential::class);
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
