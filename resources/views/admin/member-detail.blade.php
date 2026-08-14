@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細')
@section('page-kind', 'member-detail')

@section('content')
@php
    $adminUser = auth('admin')->user();
    $canManage = $adminUser === null || $adminUser->hasPermission('members.manage');
    $user = $view->member->user;
    $memberName = $user?->name ?? $view->member->member_number;
    $lineName = trim((string) ($user?->line_name ?? ''));
    $kana = trim((string) ($user?->kana ?? ''));
    $phone = trim((string) ($user?->phone ?? ''));
    $email = trim((string) ($user?->email ?? ''));
    $rankLabel = $view->member->rank_label ?? ($view->member->rank ?? 'ランク未設定');
    $statusLabel = \App\Enums\MemberStatus::getLabel((string) $view->member->status);
    $isActive = $view->member->status === \App\Enums\MemberStatus::ACTIVE;
    $channelLabel = \App\Support\MemberChannel::label($user);
    $pointsExpiry = is_array($view->member->points_expiry) ? $view->member->points_expiry : [];
    $pointsExpiring = (int) ($pointsExpiry['points_expiring'] ?? 0);
    $pointsExpiryLabel = $pointsExpiry['expires_at_label'] ?? '—';
    $recentOrders = $view->purchaseSummary['recent_orders'] ?? collect();
    $orderListTotal = (int) ($view->purchaseSummary['order_list_total'] ?? $recentOrders->count());
    $avatarChar = mb_substr($memberName !== '' ? $memberName : (string) $view->member->member_number, 0, 1);
    $sexLabel = match ((string) ($view->member->sex ?? '')) {
        '1' => '男性',
        '2' => '女性',
        '0' => '未設定',
        default => '—',
    };
    $addressLabel = implode(' ', array_filter([
        $view->member->postal_code !== null && $view->member->postal_code !== '' ? '〒'.$view->member->postal_code : '',
        $view->member->prefecture,
        $view->member->city,
        $view->member->street,
        $view->member->address,
    ], fn ($part) => $part !== null && $part !== ''));
    $firstStamp = $view->member->memberStamps->first();
    $hasProfileExtras = $view->member->birthday !== null
        || ($sexLabel !== '—' && $sexLabel !== '未設定')
        || $addressLabel !== ''
        || $view->member->notification_preference !== null;
    $historyTabs = [
        ['id' => 'orders', 'label' => '購入', 'count' => $orderListTotal],
        ['id' => 'visits', 'label' => '来店', 'count' => $view->visits?->total() ?? 0],
        ['id' => 'points', 'label' => 'ポイント', 'count' => $view->pointTransactions?->total() ?? 0],
        ['id' => 'coupons', 'label' => 'クーポン発行', 'count' => $view->memberCoupons?->total() ?? 0],
        ['id' => 'redemptions', 'label' => 'クーポン使用', 'count' => $view->couponRedemptions?->total() ?? 0],
        ['id' => 'rank', 'label' => 'ランク', 'count' => $view->rankHistories?->total() ?? 0],
        ['id' => 'stamps', 'label' => 'スタンプ', 'count' => $view->stampTransactions?->total() ?? 0],
    ];
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.members') }}">会員一覧</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $memberName }}</span>
</nav>

<section class="member-identity" aria-labelledby="member-identity-name">
    <div class="member-avatar" aria-hidden="true">{{ $avatarChar }}</div>
    <div class="member-identity-main">
        <div class="member-identity-top">
            <div>
                <h1 class="member-identity-name" id="member-identity-name">{{ $memberName }}</h1>
                @if($kana !== '')
                <p class="member-identity-kana">{{ $kana }}</p>
                @endif
                <p class="member-identity-registered">登録 {{ $view->member->created_at->format('Y/m/d') }}</p>
                <div class="member-identity-meta">
                    <code class="member-number">{{ $view->member->member_number }}</code>
                    <span class="member-rank-chip" data-rank="{{ $view->member->rank ?? '' }}">{{ $rankLabel }}</span>
                    <span class="badge badge-{{ $isActive ? 'success' : 'danger' }}">{{ $statusLabel }}</span>
                    @if($lineName !== '')
                    <span class="member-chip member-chip--line"><i class="fab fa-line" aria-hidden="true"></i>LINE名{{ $lineName !== $memberName ? ' '.$lineName : '' }}</span>
                    @endif
                    @if($channelLabel !== '—')
                    <span class="member-chip">{{ $channelLabel }}</span>
                    @endif
                </div>
            </div>
            @if($canManage)
            <div class="member-identity-actions">
                <a href="{{ admin_route('admin.members.edit', ['id' => $view->member->id]) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> 編集
                </a>
                <form action="{{ admin_route('admin.members.delete', ['id' => $view->member->id]) }}" method="POST" class="d-inline delete-form" data-item-name="{{ $memberName }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm no-loading"><i class="fas fa-trash"></i> 削除</button>
                </form>
            </div>
            @endif
        </div>
        @if($phone !== '' || $email !== '')
        <p class="member-identity-contact">
            @if($phone !== '')
            <a href="tel:{{ $phone }}"><i class="fas fa-phone" aria-hidden="true"></i>{{ $phone }}</a>
            @endif
            @if($email !== '')
            <a href="mailto:{{ $email }}"><i class="fas fa-envelope" aria-hidden="true"></i>{{ $email }}</a>
            @endif
        </p>
        @endif
        <div class="member-identity-stats">
            <div class="member-identity-stat">
                <div class="member-identity-stat-label">ポイント</div>
                <div class="member-identity-stat-value">{{ number_format($view->member->points) }}<span>pt</span></div>
                @if($pointsExpiring > 0)
                <div class="member-identity-stat-sub">{{ $pointsExpiryLabel }}に {{ number_format($pointsExpiring) }}pt 期限切れ</div>
                @endif
            </div>
            <div class="member-identity-stat">
                <div class="member-identity-stat-label">来店回数</div>
                <div class="member-identity-stat-value">{{ number_format((int) ($view->member->visits_count ?? 0)) }}<span>回</span></div>
                <div class="member-identity-stat-sub">
                    {{ $view->member->last_visited_at ? '最終 '.$view->member->last_visited_at->format('Y/m/d H:i') : '来店なし' }}
                </div>
            </div>
            @if($firstStamp !== null)
            <div class="member-identity-stat">
                <div class="member-identity-stat-label">スタンプ</div>
                <div class="member-identity-stat-value">{{ $firstStamp->current_stamps }}<span> / {{ $firstStamp->stampCard?->required_stamps ?? 0 }}</span></div>
                <div class="member-identity-stat-sub">{{ $firstStamp->stampCard?->name ?? 'スタンプカード' }}{{ $firstStamp->completed_at ? '（達成済み）' : '' }}</div>
            </div>
            @endif
        </div>
    </div>
</section>

<div class="member-kpi-grid">
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--ltv"><i class="fas fa-yen-sign"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">累計購買額（LTV）</div>
            <div class="member-kpi-value">¥{{ number_format($view->purchaseSummary['total_revenue'] ?? 0) }}</div>
        </div>
    </div>
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--orders"><i class="fas fa-shopping-cart"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">総注文数</div>
            <div class="member-kpi-value">{{ number_format($view->purchaseSummary['total_orders'] ?? 0) }}件</div>
        </div>
    </div>
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--avg"><i class="fas fa-chart-bar"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">平均注文単価</div>
            <div class="member-kpi-value">¥{{ number_format($view->purchaseSummary['avg_order_value'] ?? 0) }}</div>
        </div>
    </div>
    <div class="member-kpi-card {{ isset($view->purchaseSummary['days_since_last_order']) && $view->purchaseSummary['days_since_last_order'] > 90 ? 'member-kpi-card--alert' : '' }}">
        <div class="member-kpi-icon member-kpi-icon--last {{ isset($view->purchaseSummary['days_since_last_order']) && $view->purchaseSummary['days_since_last_order'] > 90 ? 'member-kpi-icon--danger' : '' }}"><i class="fas fa-clock"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">最終購買日</div>
            <div class="member-kpi-value">
                @if(isset($view->purchaseSummary['last_order_at']) && $view->purchaseSummary['last_order_at'])
                    {{ $view->purchaseSummary['last_order_at']->format('Y/m/d') }}
                    <span class="member-kpi-sub">{{ $view->purchaseSummary['days_since_last_order'] }}日前</span>
                @else
                    <span class="text-muted">購買なし</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="member-detail-split{{ $hasProfileExtras ? '' : ' member-detail-split--single' }}">
@if($hasProfileExtras)
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-id-card"></i> プロフィール</h3>
    </div>
    <div class="card-body">
        <dl class="member-dl">
            @if($view->member->birthday)
            <div class="member-dl-row">
                <dt>生年月日</dt>
                <dd>{{ $view->member->birthday->format('Y/m/d') }}</dd>
            </div>
            @endif
            @if($sexLabel !== '—' && $sexLabel !== '未設定')
            <div class="member-dl-row">
                <dt>性別</dt>
                <dd>{{ $sexLabel }}</dd>
            </div>
            @endif
            @if($addressLabel !== '')
            <div class="member-dl-row">
                <dt>住所</dt>
                <dd>{{ $addressLabel }}</dd>
            </div>
            @endif
            <div class="member-dl-row">
                <dt>お知らせ通知</dt>
                <dd>{{ \App\Enums\MemberNotificationPreference::getLabel(\App\Enums\MemberNotificationPreference::normalize($view->member->notification_preference)) }}</dd>
            </div>
        </dl>
    </div>
</div>
@endif

<div class="card member-staff-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags"></i> タグ・メモ</h3>
        <a href="{{ admin_route('admin.member-tags') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-cog"></i> タグ管理
        </a>
    </div>
    <div class="card-body">
        <div class="member-tag-section">
            <div class="member-tag-label-row">
                <span class="form-label">付与中のタグ</span>
            </div>
            <div class="member-tag-list" id="member-tag-list">
                @forelse($view->member->tags as $tag)
                <span class="member-tag-item">
                    <span class="member-tag-badge" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                    @if($canManage)
                    <form action="{{ admin_route('admin.members.tags.detach', ['id' => $view->member->id, 'tagId' => $tag->id]) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="member-tag-remove" title="解除"><i class="fas fa-times"></i></button>
                    </form>
                    @endif
                </span>
                @empty
                <span class="text-muted small">タグなし</span>
                @endforelse
            </div>
            @if($canManage && $allTags->isNotEmpty())
            <form action="{{ admin_route('admin.members.tags.attach', ['id' => $view->member->id]) }}" method="POST" class="member-tag-attach-form">
                @csrf
                <select name="tag_id" class="form-control form-control-sm" required>
                    <option value="">タグを付与...</option>
                    @foreach($allTags as $tag)
                    @if(!$view->member->tags->contains('id', $tag->id))
                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endif
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> 付与</button>
            </form>
            @endif
        </div>
        <div class="member-memo-section">
            <div class="form-label">スタッフメモ</div>
            @if($canManage)
            <form action="{{ admin_route('admin.members.memo', ['id' => $view->member->id]) }}" method="POST">
                @csrf @method('PUT')
                <textarea name="memo" class="form-control member-memo-input" rows="5" maxlength="2000" placeholder="スタッフ向けのメモを入力（会員には表示されません）">{{ old('memo', $view->member->memo) }}</textarea>
                <div class="form-actions member-memo-save">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> メモを保存</button>
                </div>
            </form>
            @else
            <div class="form-control-static">{{ $view->member->memo ?? '—' }}</div>
            @endif
        </div>
    </div>
</div>
</div>

<section class="member-history-shell" aria-label="履歴">
    <div class="member-history-tablist" role="tablist" aria-label="履歴の種類">
        @foreach($historyTabs as $tab)
        <button
            type="button"
            class="member-history-tab{{ $loop->first ? ' is-active' : '' }} no-loading"
            role="tab"
            id="member-history-tab-{{ $tab['id'] }}"
            data-history-tab-btn="{{ $tab['id'] }}"
            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
            aria-controls="member-history-{{ $tab['id'] }}"
            tabindex="{{ $loop->first ? '0' : '-1' }}"
        >
            {{ $tab['label'] }}
            <span class="member-history-tab-count">{{ number_format($tab['count']) }}</span>
        </button>
        @endforeach
    </div>

<div class="card" data-history-tab="orders" id="member-history-orders" role="tabpanel" aria-labelledby="member-history-tab-orders">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shopping-bag"></i> 購入履歴
            <span class="card-header-note">{{ $orderListTotal }}件</span>
        </h3>
        @if($view->member->user_id !== null)
        <a href="{{ admin_route('admin.orders', ['user_id' => $view->member->user_id]) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-right"></i> すべての注文
        </a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>注文番号</th>
                    <th>金額</th>
                    <th>ステータス</th>
                    <th>日時</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr>
                    <td><a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}">{{ $order->formattedOrderNumber() }}</a></td>
                    <td>¥{{ number_format($order->total_amount) }}</td>
                    <td>
                        <span class="badge badge-{{ $order->status === \App\Enums\OrderStatus::COMPLETED ? 'success' : ($order->status === \App\Enums\OrderStatus::PENDING ? 'warning' : ($order->status === \App\Enums\OrderStatus::CANCELLED ? 'danger' : 'info')) }}">
                            {{ \App\Enums\OrderStatus::getLabel($order->status) }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                    <td class="table-actions-cell">
                        <a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <p>購入履歴がありません</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orderListTotal > $recentOrders->count())
    <p class="member-history-more">
        最新{{ $recentOrders->count() }}件を表示しています。
        @if($view->member->user_id !== null)
        <a href="{{ admin_route('admin.orders', ['user_id' => $view->member->user_id]) }}">残りの{{ $orderListTotal - $recentOrders->count() }}件を見る</a>
        @endif
    </p>
    @endif
</div>

@include('admin.partials.member-history.panel', [
    'section' => 'visits',
    'title' => '来店履歴',
    'icon' => 'fas fa-store',
    'placeholder' => 'メモ・商品・注文番号',
    'queryKey' => 'visits_q',
    'fromKey' => 'visits_from',
    'toKey' => 'visits_to',
    'items' => $view->visits,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'points',
    'title' => 'ポイント履歴',
    'icon' => 'fas fa-coins',
    'placeholder' => '説明・種別・注文番号',
    'queryKey' => 'points_q',
    'fromKey' => 'points_from',
    'toKey' => 'points_to',
    'items' => $view->pointTransactions,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'coupons',
    'title' => 'クーポン発行履歴',
    'icon' => 'fas fa-ticket-alt',
    'placeholder' => 'クーポン名・状態',
    'queryKey' => 'coupons_q',
    'fromKey' => 'coupons_from',
    'toKey' => 'coupons_to',
    'items' => $view->memberCoupons,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'redemptions',
    'title' => 'クーポン使用履歴',
    'icon' => 'fas fa-receipt',
    'placeholder' => 'クーポン名',
    'queryKey' => 'redemptions_q',
    'fromKey' => 'redemptions_from',
    'toKey' => 'redemptions_to',
    'items' => $view->couponRedemptions,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'rank',
    'title' => 'ランクアップ履歴',
    'icon' => 'fas fa-trophy',
    'placeholder' => 'ランク名',
    'queryKey' => 'rank_q',
    'fromKey' => 'rank_from',
    'toKey' => 'rank_to',
    'items' => $view->rankHistories,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'stamps',
    'title' => 'スタンプ付与履歴',
    'icon' => 'fas fa-stamp',
    'placeholder' => 'カード名・説明',
    'queryKey' => 'stamps_q',
    'fromKey' => 'stamps_from',
    'toKey' => 'stamps_to',
    'items' => $view->stampTransactions,
    'member' => $view->member,
    'historyFilters' => $view->historyFilters,
])
</section>
@endsection

@push('scripts')
<script>
window.MemberDetailHistoryConfig = {
    memberId: {{ (int) $view->member->id }},
};
(function () {
    const shell = document.querySelector('.member-history-shell');
    if (shell === null) {
        return;
    }
    const buttons = Array.from(shell.querySelectorAll('[data-history-tab-btn]'));
    const panels = Array.from(shell.querySelectorAll('[data-history-tab]'));
    if (buttons.length === 0 || panels.length === 0) {
        return;
    }
    const sectionIds = panels.map((panel) => panel.getAttribute('data-history-tab') || '').filter((id) => id !== '');
    const activate = (id) => {
        buttons.forEach((button) => {
            const isActive = button.getAttribute('data-history-tab-btn') === id;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            button.tabIndex = isActive ? 0 : -1;
        });
        panels.forEach((panel) => {
            panel.hidden = panel.getAttribute('data-history-tab') !== id;
        });
        shell.classList.add('is-ready');
    };
    const params = new URLSearchParams(window.location.search);
    const hash = window.location.hash.replace(/^#/, '');
    let initial = sectionIds[0] || 'orders';
    for (let i = 0; i < sectionIds.length; i++) {
        const section = sectionIds[i];
        if (
            params.get(section + '_q') !== null
            || params.get(section + '_from') !== null
            || params.get(section + '_to') !== null
            || params.get(section + '_page') !== null
        ) {
            initial = section;
            break;
        }
    }
    if (sectionIds.indexOf(hash) !== -1) {
        initial = hash;
    }
    activate(initial);
    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-history-tab-btn');
            if (id) {
                activate(id);
            }
        });
    });
})();
</script>
@endpush
