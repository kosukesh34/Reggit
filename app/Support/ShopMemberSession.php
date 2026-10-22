<?php

namespace App\Support;

use App\Models\User;
use App\Services\MemberService;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** 公開ショップの会員セッション（web guard + LIFF 互換セッション）。 */
final class ShopMemberSession
{
    public function __construct(
        private UserService $userService,
        private MemberService $memberService,
    ) {}

    public function login(User $user, Request $request): void
    {
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();
        $this->userService->rememberUser($user, (int) $user->project_id);
        $this->memberService->ensureMemberWhenRegistrationOptional($user);
        $user->forceFill(['last_login_at' => now()])->save();
    }

    public function logout(Request $request): void
    {
        $projectId = AdminContext::getCurrentProject()?->id;
        Auth::guard('web')->logout();
        if ($projectId !== null) {
            $request->session()->forget([
                'mobile_order_user_id_'.$projectId,
                'mobile_order_user_id_'.$projectId.'_version',
            ]);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function current(): ?User
    {
        $user = Auth::guard('web')->user();
        if (! $user instanceof User) {
            return null;
        }
        $projectId = AdminContext::getCurrentProject()?->id;
        if ($projectId !== null && (int) $user->project_id !== (int) $projectId) {
            return null;
        }

        return $user;
    }
}
