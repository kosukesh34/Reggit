<?php

namespace App\Http\Controllers;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Services\Chat\ChatReplyNotifier;
use App\Services\Chat\ChatStaffInbox;
use App\Services\Chat\FirebaseChatTokenIssuer;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 会員本人の会話だけを読み書きできる Firebase トークンを発行する。メッセージ自体はサーバーを経由しない。
 */
class MemberChatController extends Controller
{
    public function __construct(
        private UserService $userService,
        private FeatureSettings $features,
        private FirebaseChatTokenIssuer $tokens,
    ) {}

    public function token(Request $request): JsonResponse
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $liffHidden = $request->input('surface') === 'liff' && ! $this->features->isFeatureEnabled('feature_chat_liff_enabled', $projectId);
        if (! $this->features->isFeatureEnabled('feature_chat_enabled', $projectId) || $liffHidden || ! $this->tokens->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'チャットは利用できません'], 404);
        }
        $user = $this->userService->findUser($request);
        $member = $user?->member;
        if ($user === null || $member === null) {
            return response()->json(['success' => false, 'message' => 'チャットの利用には会員登録が必要です'], 403);
        }

        // チャットを開いたら、アプリアイコンの未読数を数え直す
        Cache::forget(ChatReplyNotifier::unreadKey($projectId, (int) $member->id));

        return response()->json(['success' => true, 'data' => [
            ...$this->tokens->forMember($projectId, (int) $member->id, (string) $user->name, $member->stores()->pluck('stores.id')->all()),
            'project_id' => $projectId,
            'member_id' => (int) $member->id,
            // 店舗の返信の横に出すアイコン
            'store_icon_url' => $this->features->getFrontendLogoUrl($projectId) ?: null,
        ]]);
    }

    /** アプリのバッジ用。チャットを開いてから届いた返信の数（Firebase には接続しない） */
    public function unread(Request $request): JsonResponse
    {
        $key = $this->unreadKey($request);

        return response()->json(['success' => true, 'data' => ['count' => $key === null ? 0 : (int) Cache::get($key, 0)]]);
    }

    /** 会員が送信した。本文は受け取らず、管理画面のメニューに未読として出すための印だけを付ける */
    public function sent(Request $request, ChatStaffInbox $inbox): JsonResponse
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $member = $this->features->isFeatureEnabled('feature_chat_enabled', $projectId)
            ? $this->userService->findUser($request)?->member
            : null;
        if ($member !== null) {
            $inbox->markUnread($projectId, (int) $member->id);
        }

        return response()->json(['success' => true]);
    }

    /** 開いたままのチャットで返信を読んだとき */
    public function read(Request $request): JsonResponse
    {
        $key = $this->unreadKey($request);
        if ($key !== null) {
            Cache::forget($key);
        }

        return response()->json(['success' => true, 'data' => ['count' => 0]]);
    }

    private function unreadKey(Request $request): ?string
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $member = $this->features->isFeatureEnabled('feature_chat_enabled', $projectId)
            ? $this->userService->findUser($request)?->member
            : null;

        return $member === null ? null : ChatReplyNotifier::unreadKey($projectId, (int) $member->id);
    }
}
