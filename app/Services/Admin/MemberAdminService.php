<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberStamp;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Models\StampCard;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\Smaregi\SmaregiApiService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MemberAdminService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator
    ) {}

    public function getPaginatedList(int $projectId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Member::with('user')
            ->where('project_id', $projectId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('member_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%'));
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $rank = (string) ($filters['rank'] ?? '');
        if ($rank !== '') {
            $query->where('rank', $rank);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findOrFail(int $id, ?int $projectId = null): Member
    {
        $query = Member::with(['user', 'pointTransactions.order', 'memberStamps.stampCard', 'memberCoupons.coupon']);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        $member = $query->findOrFail($id);

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $member->setAttribute('rank_label', $rankInfo['current_rank_label'] ?? $member->rank);
        $member->setAttribute('points_expiry', $this->expiryCalculator->getExpiryInfo($member));

        return $member;
    }

    /**
     * 会員の購買サマリー（LTV・注文数・平均単価・最終購買日・最終来店からの経過日数）
     *
     * @return array{total_orders: int, total_revenue: float, avg_order_value: float, last_order_at: ?\Carbon\Carbon, days_since_last_order: int|null}
     */
    public function getPurchaseSummary(Member $member): array
    {
        $userId = $member->user_id;

        if ($userId === null) {
            return [
                'total_orders' => 0,
                'total_revenue' => 0,
                'avg_order_value' => 0,
                'last_order_at' => null,
                'days_since_last_order' => null,
                'recent_orders' => collect(),
            ];
        }

        $baseQuery = Order::where('user_id', $userId)
            ->where('status', '!=', OrderStatus::CANCELLED);

        $totalOrders = $baseQuery->count();
        $totalRevenue = (float) $baseQuery->sum('total_amount');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;
        $lastOrder = Order::where('user_id', $userId)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->orderByDesc('created_at')
            ->first();

        $lastOrderAt = $lastOrder?->created_at;
        $daysSinceLastOrder = $lastOrderAt ? (int) $lastOrderAt->diffInDays(now()) : null;

        $recentOrders = Order::with('items.product')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'last_order_at' => $lastOrderAt,
            'days_since_last_order' => $daysSinceLastOrder,
            'recent_orders' => $recentOrders,
        ];
    }

    /** 配布用の有効クーポン一覧（同一プロジェクト） */
    public function getActiveCouponsForSelect(int $projectId): Collection
    {
        return Coupon::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 配布用の有効スタンプカード一覧（同一プロジェクト） */
    public function getActiveStampCardsForSelect(int $projectId): Collection
    {
        return StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 会員にクーポンを1件発行（管理画面からの手動配布） */
    public function issueCouponToMember(Member $member, int $couponId, ?\DateTimeInterface $validFrom = null, ?\DateTimeInterface $validUntil = null): MemberCoupon
    {
        $coupon = Coupon::where('project_id', $member->project_id)->findOrFail($couponId);
        $validFrom = $validFrom ?? now();
        $validUntil = $validUntil ?? $coupon->end_at ?? Carbon::now()->addDays(30);

        return MemberCoupon::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'coupon_name_snapshot' => $coupon->name,
            'status' => MemberCoupon::STATUS_UNUSED,
            'issued_at' => now(),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil instanceof \DateTimeInterface ? $validUntil : null,
        ]);
    }

    /** 複数会員にクーポンを一括発行（プレゼント配信）。$memberIds が空の場合は全員に配布。 */
    public function issueCouponToMembers(int $projectId, int $couponId, array $memberIds = []): int
    {
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($couponId);
        $query = Member::where('project_id', $projectId);
        if ($memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }
        $members = $query->get();
        $validUntil = $coupon->end_at ?? Carbon::now()->addDays(30);
        $issued = 0;
        foreach ($members as $member) {
            $already = MemberCoupon::where('member_id', $member->id)
                ->where('coupon_id', $couponId)
                ->where('status', MemberCoupon::STATUS_UNUSED)
                ->where(function ($q) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                })->exists();
            if ($already) {
                continue;
            }
            $this->issueCouponToMember($member, $couponId, null, $validUntil);
            $issued++;
        }

        return $issued;
    }

    /** 会員のスタンプカードにスタンプを追加（管理画面からの手動付与） */
    public function addStampsToMember(Member $member, int $stampCardId, int $count = 1): MemberStamp
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('スタンプ数は1以上を指定してください');
        }
        $stampCard = StampCard::where('project_id', $member->project_id)->findOrFail($stampCardId);
        $memberStamp = MemberStamp::firstOrCreate(
            [
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
            ],
            ['current_stamps' => 0]
        );
        if ($memberStamp->valid_until === null && $stampCard->valid_days !== null && (int) $stampCard->valid_days > 0) {
            $memberStamp->update(['valid_until' => Carbon::now()->addDays((int) $stampCard->valid_days)]);
        }
        $memberStamp->increment('current_stamps', $count);
        $memberStamp->refresh();
        $required = (int) $stampCard->required_stamps;
        if ($memberStamp->current_stamps >= $required && $memberStamp->completed_at === null) {
            $memberStamp->update(['completed_at' => now()]);
        }

        return $memberStamp->refresh();
    }

    public function getCountByProject(int $projectId): int
    {
        return Member::where('project_id', $projectId)->count();
    }

    /**
     * @param  int[]  $memberIds
     * @return Collection<int, int>
     */
    public function filterValidMemberIds(int $projectId, array $memberIds): Collection
    {
        return Member::where('project_id', $projectId)
            ->whereIn('id', $memberIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, member_number: string, name: string}>
     */
    public function searchByKeyword(int $projectId, string $keyword, int $limit = 20): Collection
    {
        $query = Member::query()
            ->where('project_id', $projectId)
            ->with(['user:id,name']);

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('member_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%'.$keyword.'%');
                    });
            });
        }

        return $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Member $member) => [
                'id' => (int) $member->id,
                'member_number' => (string) ($member->member_number ?? ''),
                'name' => (string) ($member->user?->name ?? ''),
            ])
            ->values();
    }

    /** 会員編集フォーム用：ランク選択肢（key => label） */
    public function getRankOptionsForSelect(int $projectId): array
    {
        $ranks = ShopSetting::getMemberRankSettings($projectId);
        $options = [];
        foreach ($ranks as $r) {
            if (\App\Util\StrHelper::isEmptyStringOrNull($r['key'] ?? null)) {
                continue;
            }
            $options[(string) $r['key']] = (string) ($r['label'] ?? $r['key']);
        }

        return $options;
    }

    /**
     * 会員のユーザー情報・会員情報を一括更新する。
     * ポイントの加算・減算は Controller から別途 addPoints/usePoints で行う想定。
     * スマレジ連携時はAPI更新を成功確認してからローカルを更新する。
     */
    public function updateMemberProfile(Member $member, array $userData, array $memberData): void
    {
        $user = $member->user;
        $allowedUser = ['name', 'kana', 'email', 'phone'];
        $updateUser = array_intersect_key($userData, array_flip($allowedUser));

        $smaregiCustomerId = $member->smaregi_customer_id ?? '';
        $contractId = $this->smaregiApi->getContractIdForProject($member->project_id);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId !== '' && $smaregiCustomerId !== '' && $updateUser !== [] && $user !== null) {
            $clientId = config('smaregi.client_id');
            $clientSecret = config('smaregi.client_secret');
            if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
                throw new \Exception(
                    'スマレジの認証情報がアプリに読み込まれていません。'
                    .' .env の SMAREGI_CLIENT_ID と SMAREGI_CLIENT_SECRET を確認してください。'
                    .' Docker 利用時はコンテナに環境変数が渡るように（env_file や environment）設定してください。'
                );
            }
            $merged = array_merge($user->only(['name', 'kana', 'email', 'phone']), $updateUser);
            $body = $this->buildSmaregiUpdateBody($merged);
            if ($body !== []) {
                $result = $this->smaregiApi->updateCustomer($contractId, $smaregiCustomerId, $body);
                if ($result === null) {
                    throw new \Exception(
                        'スマレジの会員情報更新に失敗しました。'
                        .' 詳細は storage/logs/laravel.log の [Smaregi] を参照してください。'
                    );
                }
            }
        }

        if ($user !== null && $updateUser !== []) {
            $user->update($updateUser);
        }

        $allowedMember = [
            'member_number', 'status', 'rank', 'birthday', 'address',
            'postal_code', 'prefecture', 'city', 'street', 'sex', 'profile_extra',
        ];
        $updateMember = array_intersect_key($memberData, array_flip($allowedMember));
        if ($updateMember !== []) {
            // rank_label / points_expiry は findOrFail で付与した仮想属性のため、DBに存在しない。update 前に外す。
            $member->offsetUnset('rank_label');
            $member->offsetUnset('points_expiry');
            $member->update($updateMember);
        }
    }

    /** スマレジ会員更新用ボディ（name → lastName/firstName 等） */
    private function buildSmaregiUpdateBody(array $userFields): array
    {
        $body = [];
        $name = trim((string) ($userFields['name'] ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/u', $name, 2);
            $body['lastName'] = $parts[0] ?? $name;
            $body['firstName'] = $parts[1] ?? '';
        }
        $kana = trim((string) ($userFields['kana'] ?? ''));
        if ($kana !== '') {
            $kanaParts = preg_split('/\s+/u', $kana, 2);
            $body['lastKana'] = $kanaParts[0] ?? $kana;
            $body['firstKana'] = $kanaParts[1] ?? '';
        }
        $email = trim((string) ($userFields['email'] ?? ''));
        if ($email !== '' && $email !== 'null' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $body['mailAddress'] = $email;
        }
        $phone = trim((string) ($userFields['phone'] ?? ''));
        if ($phone !== '' && $phone !== 'null') {
            $body['phoneNumber'] = $phone;
        }

        return $body;
    }
}
