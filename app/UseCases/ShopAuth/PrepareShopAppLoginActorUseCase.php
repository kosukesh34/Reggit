<?php

namespace App\UseCases\ShopAuth;

use App\Models\User;
use App\Support\ShopMemberSession;
use App\Util\AdminContext;
use RuntimeException;

/** ショップのアプリログイン用アクター（会員未作成のゲストのみ許可）。 */
class PrepareShopAppLoginActorUseCase
{
    public function __construct(
        private ShopMemberSession $session,
    ) {}

    public function handle(): User
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $current = $this->session->current();
        if ($current !== null) {
            if ((int) $current->project_id !== $projectId) {
                throw new RuntimeException('ログイン状態を確認できません。ページを開き直してください。');
            }
            if ($current->member()->where('project_id', $projectId)->exists()) {
                throw new RuntimeException('すでに別の会員でログインしています。いったんログアウトしてからアプリでログインしてください。');
            }

            return $current;
        }

        return User::create([
            'project_id' => $projectId,
            'line_user_id' => 'guest-shop-'.bin2hex(random_bytes(8)),
            'name' => null,
        ]);
    }
}
