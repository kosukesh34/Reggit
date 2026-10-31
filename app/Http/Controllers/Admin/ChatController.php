<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Services\Chat\FirebaseChatTokenIssuer;
use App\UseCases\Admin\Chat\AdminChatMemberContextUseCase;
use App\UseCases\Admin\Chat\AdminChatMemberUseCase;
use App\Util\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChatController extends AdminBaseController
{
    public function __construct(
        private FeatureSettings $features,
        private FirebaseChatTokenIssuer $tokens,
        private AdminChatMemberUseCase $members,
    ) {}

    public function index(): View
    {
        $this->ensureEnabled();

        return view('admin.chats', [
            'configured' => $this->tokens->isConfigured(),
            'chatConfig' => [
                'tokenUrl' => admin_route('admin.chats.token'),
                'notifyUrl' => admin_route('admin.chats.notify', ['member' => '__MEMBER__']),
                'memberUrl' => admin_route('admin.members.detail', ['id' => '__MEMBER__']),
                'contextUrl' => admin_route('admin.chats.context', ['member' => '__MEMBER__']),
                'membersUrl' => admin_route('admin.chats.members'),
                'readUrl' => admin_route('admin.chats.read', ['member' => '__MEMBER__']),
                'couponUrl' => admin_route('admin.chats.coupon', ['member' => '__MEMBER__', 'coupon' => '__COUPON__']),
                'stampUrl' => admin_route('admin.chats.stamps', ['member' => '__MEMBER__']),
                'csrfToken' => csrf_token(),
            ],
        ]);
    }

    /** 会員一覧と同じ範囲（StoreAccessScope）の会話だけを読めるトークンを発行する */
    public function token(StoreContext $context): JsonResponse
    {
        $this->ensureEnabled();
        abort_unless($this->tokens->isConfigured(), 503, 'チャットの接続設定がありません');
        $ids = $context->visibleStoreIds;
        $allStores = $ids === null || ($context->projectStoreCount <= 1 && $ids !== []);
        $admin = auth('admin')->user();
        $projectId = $this->resolveAdminProjectId();

        return response()->json([
            ...$this->tokens->forStaff($projectId, (int) $admin->id, (string) $admin->name, $allStores ? null : $ids),
            'project_id' => $projectId,
        ]);
    }

    public function notify(string $project_key, int $member): JsonResponse
    {
        $this->ensureEnabled();
        $this->members->notifyReply($member, $project_key);

        return response()->json(['success' => true]);
    }

    /** 会話一覧の会員の名前とプロフィール画像。見られる会員（StoreAccessScope）の分だけ返す */
    public function members(Request $request): JsonResponse
    {
        $this->ensureEnabled();
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];

        return response()->json(['members' => $this->members->summaries($ids)]);
    }

    /** 店舗が会話を開いて読んだ。メニューの未読数から外す */
    public function read(string $project_key, int $member): JsonResponse
    {
        $this->ensureEnabled();
        $this->members->markRead($member);

        return response()->json(['success' => true]);
    }

    /** 会話の横に出す会員の予約と、配布できるクーポン。権限のない項目は空で返す */
    public function context(string $project_key, int $member, AdminChatMemberContextUseCase $context): JsonResponse
    {
        $this->ensureEnabled();

        return response()->json($context->handle($member, auth('admin')->user()));
    }

    /** チャットからクーポンを配布する。会員詳細の手動配布と同じ処理で発行し、カードの内容を返す */
    public function issueCoupon(string $project_key, int $member, int $coupon): JsonResponse
    {
        $this->ensureEnabled();
        try {
            return response()->json($this->members->issueCoupon($member, $coupon));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** チャットからスタンプを付与する。会員詳細の「スタンプ調整」と同じ処理で加算し、カードの内容を返す */
    public function grantStamps(Request $request, string $project_key, int $member): JsonResponse
    {
        $this->ensureEnabled();
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)->where('is_active', true)],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        try {
            return response()->json($this->members->grantStamps($projectId, $member, (int) $validated['stamp_card_id'], (int) $validated['count']));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function ensureEnabled(): void
    {
        abort_unless($this->features->isFeatureEnabled('feature_chat_enabled', $this->resolveAdminProjectId()), 404);
    }
}
