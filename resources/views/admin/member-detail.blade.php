@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細')
@section('page-kind', 'member-detail')

@section('content')
@php
    $adminUser = auth('admin')->user();
    $canManage = $adminUser === null || $adminUser->hasPermission('members.manage');
    $memberName = $view->member->user->name ?? $view->member->member_number;
    $lineName = trim((string) ($view->member->user?->line_name ?? ''));
    $recentOrders = $view->purchaseSummary['recent_orders'] ?? collect();
    $orderListTotal = (int) ($view->purchaseSummary['order_list_total'] ?? $recentOrders->count());
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.members') }}">会員一覧</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $memberName }}</span>
</nav>

<div class="member-detail-head">
    <div class="member-detail-head-main">
        <p class="member-detail-kicker">会員</p>
        <h1 class="member-detail-name">{{ $memberName }}</h1>
        <p class="member-detail-meta">
            <code class="member-number">{{ $view->member->member_number }}</code>
            @if($lineName !== '')
            <span class="member-detail-line-name"><i class="fab fa-line" aria-hidden="true"></i>{{ $lineName }}</span>
            @endif
            <span>{{ $view->member->rank_label ?? ($view->member->rank ?? 'ランク未設定') }}</span>
            <span class="badge badge-{{ $view->member->status === 'active' ? 'success' : 'danger' }}">
                {{ $view->member->status === 'active' ? '有効' : '無効' }}
            </span>
        </p>
    </div>
    @if($canManage)
    <div class="member-detail-head-actions">
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

{{-- 購買サマリーKPI --}}
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

<div class="member-detail-split">
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user"></i> 会員情報</h3>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">ID（内部）</div>
                <div class="info-value"><span class="id-badge">{{ $view->member->id }}</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">会員番号</div>
                <div class="info-value"><code class="member-number">{{ $view->member->member_number }}</code></div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー名</div>
                <div class="info-value">{{ $view->member->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">LINE名</div>
                <div class="info-value">{{ $lineName !== '' ? $lineName : '—' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">現在のポイント</div>
                <div class="info-value">{{ number_format($view->member->points) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">ランク</div>
                <div class="info-value">{{ $view->member->rank_label ?? ($view->member->rank ?? '—') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント有効期限</div>
                <div class="info-value">
                    {{ $view->member->points_expiry['expires_at_label'] ?? '—' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">期限切れ予定ポイント</div>
                <div class="info-value">
                    {{ number_format($view->member->points_expiry['points_expiring'] ?? 0) }}pt
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
                    <span class="badge badge-{{ $view->member->status === 'active' ? 'success' : 'danger' }}">
                        {{ $view->member->status === 'active' ? '有効' : '無効' }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">登録日</div>
                <div class="info-value">{{ $view->member->created_at->format('Y/m/d H:i:s') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">登録チャネル</div>
                <div class="info-value">
                    @php $channelLabel = \App\Support\MemberChannel::label($view->member->user); @endphp
                    @if($channelLabel === '—')
                        <span class="text-muted">—</span>
                    @else
                        <span class="badge badge-info">{{ $channelLabel }}</span>
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">最終来店日時</div>
                <div class="info-value">
                    {{ $view->member->last_visited_at ? $view->member->last_visited_at->format('Y/m/d H:i') : '—' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">来店回数</div>
                <div class="info-value">{{ number_format((int) ($view->member->visits_count ?? 0)) }}回</div>
            </div>
        </div>
        @if($canManage)
        <p class="text-muted small mb-0 mt-3">ポイント・スタンプ・クーポン・来店の操作は<a href="{{ admin_route('admin.members.edit', ['id' => $view->member->id]) }}">編集画面</a>から行えます。</p>
        @endif
    </div>
</div>

<div class="card">
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
                <textarea name="memo" class="form-control" rows="3" maxlength="2000" placeholder="スタッフ向けのメモを入力（会員には表示されません）">{{ old('memo', $view->member->memo) }}</textarea>
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

{{-- 購入履歴（最新10件。残りは注文詳細・注文一覧） --}}
<div class="card">
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
@endsection

@push('scripts')
<script>
window.MemberDetailHistoryConfig = {
    memberId: {{ (int) $view->member->id }},
};
</script>
@endpush
