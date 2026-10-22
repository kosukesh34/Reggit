<?php

namespace App\UseCases\ShopAuth;

use App\Models\User;
use App\Support\ShopMemberSession;
use App\Util\AdminContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConsumeShopMagicLinkUseCase
{
    public function __construct(
        private ShopMemberSession $session,
    ) {}

    public function handle(string $email): User
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('メールアドレスが不正です。');
        }

        return DB::transaction(function () use ($email, $projectId): User {
            $byEmail = User::query()
                ->where('project_id', $projectId)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($byEmail !== null) {
                if ($byEmail->email_verified_at === null) {
                    $byEmail->email_verified_at = now();
                    $byEmail->save();
                }

                return $byEmail;
            }

            $current = $this->session->current();
            if ($current !== null && (int) $current->project_id === $projectId) {
                $occupied = User::query()
                    ->where('project_id', $projectId)
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('id', '!=', $current->id)
                    ->exists();
                if ($occupied) {
                    throw new RuntimeException('このメールアドレスは別の会員に使われています。いったんログアウトしてからやり直してください。');
                }
                $current->email = $email;
                $current->email_verified_at = now();
                $current->save();

                return $current;
            }

            $user = User::create([
                'project_id' => $projectId,
                'line_user_id' => 'guest-mail-'.bin2hex(random_bytes(8)),
                'email' => $email,
                'name' => strstr($email, '@', true) ?: $email,
            ]);
            // email_verified_at は fillable 外なので create では保存されない
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });
    }
}
