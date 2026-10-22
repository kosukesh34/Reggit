<?php

namespace App\UseCases\ShopAuth;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Services\Shop\LineLoginClient;
use App\Support\MemberChannel;
use App\Util\AdminContext;
use RuntimeException;

class LoginWithLineUseCase
{
    public function __construct(
        private LineLoginClient $lineLoginClient,
        private UserRepository $userRepository,
    ) {}

    public function handle(string $code, string $redirectUri): User
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $token = $this->lineLoginClient->exchangeCode($code, $redirectUri);
        $profile = $this->lineLoginClient->fetchProfile($token['access_token']);
        $lineUserId = $profile['userId'];
        if (! MemberChannel::isValidLineUserId($lineUserId)) {
            throw new RuntimeException('LINEユーザーIDが不正です。');
        }

        // Web では会員登録・LINE 連携の付け替えをせず、登録済み会員のログインだけを許可する
        $user = $this->userRepository->findByLineUserIdAndProject($lineUserId, $projectId);
        if ($user === null || ! $user->member()->where('project_id', $projectId)->exists()) {
            throw new RuntimeException('このLINEアカウントは会員登録されていません。会員登録済みのLINEアカウントでログインしてください。');
        }
        $this->fillProfile($user, $profile);

        return $user;
    }

    /**
     * @param  array{displayName?: string, pictureUrl?: string}  $profile
     */
    private function fillProfile(User $user, array $profile): void
    {
        $dirty = false;
        if (($user->name === null || $user->name === '') && isset($profile['displayName'])) {
            $user->name = $profile['displayName'];
            $dirty = true;
        }
        if (isset($profile['displayName'])) {
            $user->line_name = $profile['displayName'];
            $dirty = true;
        }
        if (isset($profile['pictureUrl'])) {
            $user->profile_image_url = $profile['pictureUrl'];
            $dirty = true;
        }
        if ($dirty) {
            $user->save();
        }
    }
}
