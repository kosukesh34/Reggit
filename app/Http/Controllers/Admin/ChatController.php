<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Enums\ReservationStatus;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\Reservation;
use App\Models\StampCard;
use App\Services\Chat\ChatReplyNotifier;
use App\Services\Chat\ChatStaffInbox;
use App\Services\Chat\FirebaseChatTokenIssuer;
use App\Services\MemberBenefitService;
use App\UseCases\Admin\Member\AdminMemberAdjustStampsUseCase;
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

    public function notify(string $project_key, int $member, ChatReplyNotifier $notifier, ChatStaffInbox $inbox): JsonResponse
    {
        $this->ensureEnabled();
        $target = Member::query()->with('user')->findOrFail($member);
        $notifier->notify($target, $project_key);
        // 返信したら店舗側の未読も消す
        $inbox->markRead((int) $target->project_id, (int) $target->id);

        return response()->json(['success' => true]);
    }

    /** 会話一覧の会員の名前とプロフィール画像。見られる会員（StoreAccessScope）の分だけ返す */
    public function members(Request $request): JsonResponse
    {
        $this->ensureEnabled();
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];
        $members = Member::query()->with('user:id,name,profile_image_path')->whereIn('id', $ids)->get(['id', 'user_id']);

        return response()->json(['members' => $members->mapWithKeys(fn (Member $member): array => [$member->id => [
            'name' => admin_person_name($member->user?->name),
            'avatar_url' => $member->user?->profile_image_path ? asset('storage/'.ltrim($member->user->profile_image_path, '/')) : null,
        ]])]);
    }

    /** 店舗が会話を開いて読んだ。メニューの未読数から外す */
    public function read(string $project_key, int $member, ChatStaffInbox $inbox): JsonResponse
    {
        $this->ensureEnabled();
        $target = Member::query()->findOrFail($member);
        $inbox->markRead((int) $target->project_id, (int) $target->id);

        return response()->json(['success' => true]);
    }

    /** 会話の横に出す会員の予約と、配布できるクーポン。権限のない項目は空で返す */
    public function context(string $project_key, int $member): JsonResponse
    {
        $this->ensureEnabled();
        $target = Member::query()->findOrFail($member);
        $admin = auth('admin')->user();
        $reservations = $admin->hasPermission('reservations.view') && $target->user_id !== null
            ? Reservation::query()
                ->where('user_id', $target->user_id)
                ->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
                ->where('reserved_at', '>=', now()->startOfDay())
                ->orderBy('reserved_at')
                ->limit(10)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'id' => $reservation->id,
                    'number' => (string) $reservation->reservation_number,
                    'reserved_at' => $reservation->reserved_at?->toIso8601String(),
                    'people' => (int) $reservation->number_of_people,
                    'course' => (string) ($reservation->course_name ?? ''),
                    'status' => ReservationStatus::getLabel((string) $reservation->status),
                ])->all()
            : [];
        $coupons = $admin->hasPermission('members.manage')
            ? Coupon::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('end_at')->orWhere('end_at', '>', now()))
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'end_at'])
                ->map(fn (Coupon $coupon): array => ['id' => $coupon->id, 'name' => $coupon->name, 'end_at' => $coupon->end_at?->toIso8601String()])
                ->all()
            : [];

        $canManage = $admin->hasPermission('members.manage');
        $current = MemberStamp::query()->where('member_id', $target->id)->pluck('current_stamps', 'stamp_card_id');
        $stampCards = $canManage
            ? StampCard::query()->where('is_active', true)->orderBy('id')->limit(50)->get(['id', 'name', 'required_stamps'])
                ->map(fn (StampCard $card): array => [
                    'id' => $card->id,
                    'name' => $card->name,
                    'required' => (int) $card->required_stamps,
                    'current' => (int) ($current[$card->id] ?? 0),
                ])->all()
            : [];

        return response()->json(['reservations' => $reservations, 'coupons' => $coupons, 'stamp_cards' => $stampCards, 'can_issue_coupons' => $canManage]);
    }

    /** チャットからクーポンを配布する。会員詳細の手動配布と同じ処理で発行し、カードの内容を返す */
    public function issueCoupon(string $project_key, int $member, int $coupon, MemberBenefitService $benefits): JsonResponse
    {
        $this->ensureEnabled();
        $target = Member::query()->findOrFail($member);
        try {
            $issued = $benefits->issueCouponToMember($target, Coupon::query()->where('is_active', true)->findOrFail($coupon)->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'name' => (string) $issued->coupon_name_snapshot,
            'valid_until' => $issued->valid_until?->toIso8601String(),
        ]);
    }

    /** チャットからスタンプを付与する。会員詳細の「スタンプ調整」と同じ処理で加算し、カードの内容を返す */
    public function grantStamps(Request $request, string $project_key, int $member, AdminMemberAdjustStampsUseCase $stamps): JsonResponse
    {
        $this->ensureEnabled();
        $target = Member::query()->findOrFail($member);
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)->where('is_active', true)],
            'count' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        try {
            $stamps->handle($projectId, (int) $target->id, (int) $validated['stamp_card_id'], (int) $validated['count'], 0, 'チャットで付与');
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $card = StampCard::query()->findOrFail($validated['stamp_card_id']);
        $current = (int) MemberStamp::query()->where('member_id', $target->id)->where('stamp_card_id', $card->id)->value('current_stamps');

        return response()->json(['name' => $card->name, 'count' => (int) $validated['count'], 'current' => $current, 'required' => (int) $card->required_stamps]);
    }

    private function ensureEnabled(): void
    {
        abort_unless($this->features->isFeatureEnabled('feature_chat_enabled', $this->resolveAdminProjectId()), 404);
    }
}
