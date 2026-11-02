<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Services\Admin\Data\AdminRedirectResult;
use Illuminate\Support\Facades\Session;

/** 管理画面のパスワード後パスキー（必須）セッション状態。 */
final class AdminPasskeyGate
{
    public const PASSWORD_AUTHENTICATED = 'admin_password_authenticated';

    public const PASSKEY_VERIFIED = 'admin_passkey_verified';

    public const REMOTE_ENROLL = 'admin_passkey_remote_enroll';

    public const REMOTE_ENROLL_PENDING = 'admin_passkey_remote_enroll_pending';

    public static function markPasswordAuthenticated(): void
    {
        Session::put(self::PASSWORD_AUTHENTICATED, true);
        Session::forget(self::PASSKEY_VERIFIED);
        Session::forget(self::REMOTE_ENROLL);
        if (app()->environment('local') && config('passkeys.admin_required') === false) {
            Session::put(self::PASSKEY_VERIFIED, true);
        }
    }

    public static function markPasskeyVerified(): void
    {
        Session::put(self::PASSKEY_VERIFIED, true);
        Session::forget(self::REMOTE_ENROLL);
        Session::forget(self::REMOTE_ENROLL_PENDING);
    }

    public static function markRemoteEnroll(): void
    {
        Session::put(self::REMOTE_ENROLL, true);
    }

    public static function rememberRemoteEnrollPending(int $adminId, string $hash): void
    {
        Session::put(self::REMOTE_ENROLL_PENDING, [
            'admin_id' => $adminId,
            'hash' => $hash,
        ]);
    }

    public static function consumeRemoteEnrollPending(AdminUser $admin): void
    {
        $pending = Session::pull(self::REMOTE_ENROLL_PENDING);
        if (! is_array($pending)) {
            return;
        }
        if ((int) ($pending['admin_id'] ?? 0) !== (int) $admin->getAuthIdentifier()) {
            return;
        }
        if (! hash_equals(sha1((string) $admin->getEmailForPasswordReset()), (string) ($pending['hash'] ?? ''))) {
            return;
        }
        self::markRemoteEnroll();
    }

    public static function isRemoteEnroll(): bool
    {
        return Session::get(self::REMOTE_ENROLL) === true;
    }

    public static function requiresPasskeyStep(): bool
    {
        return Session::get(self::PASSWORD_AUTHENTICATED) === true
            && Session::get(self::PASSKEY_VERIFIED) !== true;
    }

    public static function isPasskeyVerified(): bool
    {
        return Session::get(self::PASSKEY_VERIFIED) === true;
    }

    public static function nextStep(AdminUser $admin): AdminRedirectResult
    {
        self::consumeRemoteEnrollPending($admin);
        if (self::isRemoteEnroll()) {
            return new AdminRedirectResult('admin.passkey.enroll');
        }

        return $admin->hasPasskeysEnabled()
            ? new AdminRedirectResult('admin.passkey.verify')
            : new AdminRedirectResult('admin.passkey.enroll');
    }

    public static function rememberIntended(AdminRedirectResult $result): void
    {
        Session::put('url.intended', route($result->route, $result->parameters));
    }
}
