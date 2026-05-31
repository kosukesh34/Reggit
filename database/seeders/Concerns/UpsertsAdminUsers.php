<?php

namespace Database\Seeders\Concerns;

use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

trait UpsertsAdminUsers
{
    public static function isDemoSeedEnabled(): bool
    {
        return filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * .env で # がコメント扱いされ切れた値を、ドキュメント記載の既定パスワードに補正する。
     *
     * @param  list<string>  $truncatedValues
     */
    public static function resolveDemoPassword(string $envKey, string $default, array $truncatedValues = []): string
    {
        $password = env($envKey);
        if ($password === null || $password === '' || in_array($password, $truncatedValues, true)) {
            return $default;
        }

        return $password;
    }

    /**
     * 管理者を upsert する。
     * - 新規: .env のパスワードで作成
     * - 既存で DB のハッシュが .env と一致しない場合のみ修復（# 切り捨て等）
     * - 既存で一致する場合はパスワードを触らない（手動変更後の再 seed で戻らない）
     */
    protected static function upsertAdminWithoutResettingPassword(
        string $loginId,
        string $email,
        string $password,
        string $name,
        string $role,
        ?int $projectId = null,
    ): AdminUser {
        $user = AdminUser::firstOrNew(['login_id' => $loginId]);
        $user->email = $email;
        $user->name = $name;
        $user->role = $role;
        $user->project_id = $projectId;
        if (! $user->exists || self::shouldRepairAdminPassword($user, $password)) {
            $user->password = $password;
        }
        $user->save();

        return $user;
    }

    private static function shouldRepairAdminPassword(AdminUser $user, string $password): bool
    {
        if ($user->password === null || $user->password === '') {
            return true;
        }

        return Hash::check($password, $user->password) === false;
    }
}
