@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細 #' . $member->id)
@section('page-kind', 'member-detail')

@section('content')

{{-- 購買サマリーKPI --}}
<div class="member-kpi-grid">
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--ltv"><i class="fas fa-yen-sign"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">累計購買額（LTV）</div>
            <div class="member-kpi-value">¥{{ number_format($purchaseSummary['total_revenue'] ?? 0) }}</div>
        </div>
    </div>
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--orders"><i class="fas fa-shopping-cart"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">総注文数</div>
            <div class="member-kpi-value">{{ number_format($purchaseSummary['total_orders'] ?? 0) }}件</div>
        </div>
    </div>
    <div class="member-kpi-card">
        <div class="member-kpi-icon member-kpi-icon--avg"><i class="fas fa-chart-bar"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">平均注文単価</div>
            <div class="member-kpi-value">¥{{ number_format($purchaseSummary['avg_order_value'] ?? 0) }}</div>
        </div>
    </div>
    <div class="member-kpi-card {{ isset($purchaseSummary['days_since_last_order']) && $purchaseSummary['days_since_last_order'] > 90 ? 'member-kpi-card--alert' : '' }}">
        <div class="member-kpi-icon member-kpi-icon--last {{ isset($purchaseSummary['days_since_last_order']) && $purchaseSummary['days_since_last_order'] > 90 ? 'member-kpi-icon--danger' : '' }}"><i class="fas fa-clock"></i></div>
        <div class="member-kpi-body">
            <div class="member-kpi-label">最終購買日</div>
            <div class="member-kpi-value">
                @if(isset($purchaseSummary['last_order_at']) && $purchaseSummary['last_order_at'])
                    {{ $purchaseSummary['last_order_at']->format('Y/m/d') }}
                    <span class="member-kpi-sub">{{ $purchaseSummary['days_since_last_order'] }}日前</span>
                @else
                    <span class="text-muted">購買なし</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-user"></i> 会員情報</h3>
        <div>
            @php $adminUser = auth('admin')->user(); @endphp
            @if($adminUser === null || $adminUser->hasPermission('members.manage'))
            <a href="{{ admin_route('admin.members.edit', ['id' => $member->id]) }}" class="btn btn-primary btn-sm me-1">
                <i class="fas fa-edit"></i> 編集
            </a>
            <form action="{{ admin_route('admin.members.delete', ['id' => $member->id]) }}" method="POST" class="d-inline me-1 delete-form" data-item-name="{{ $member->user->name ?? $member->member_number }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm no-loading"><i class="fas fa-trash"></i> 削除</button>
            </form>
            @endif
            <a href="{{ admin_route('admin.members') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">ID（内部）</div>
                <div class="info-value"><span class="id-badge">{{ $member->id }}</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">会員番号</div>
                <div class="info-value"><code class="member-number">{{ $member->member_number }}</code></div>
            </div>
            <div class="info-item">
                <div class="info-label">ユーザー名</div>
                <div class="info-value">{{ $member->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">現在のポイント</div>
                <div class="info-value">{{ number_format($member->points) }}pt</div>
            </div>
            <div class="info-item">
                <div class="info-label">ランク</div>
                <div class="info-value">{{ $member->rank_label ?? ($member->rank ?? '—') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">ポイント有効期限</div>
                <div class="info-value">
                    {{ $member->points_expiry['expires_at_label'] ?? '—' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">期限切れ予定ポイント</div>
                <div class="info-value">
                    {{ number_format($member->points_expiry['points_expiring'] ?? 0) }}pt
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">ステータス</div>
                <div class="info-value">
                    <span class="badge badge-{{ $member->status === 'active' ? 'success' : 'danger' }}">
                        {{ $member->status === 'active' ? '有効' : '無効' }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">登録日</div>
                <div class="info-value">{{ $member->created_at->format('Y/m/d H:i:s') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">登録チャネル</div>
                <div class="info-value">
                    @php $channelLabel = \App\Support\MemberChannel::label($member->user); @endphp
                    @if($channelLabel === '—')
                        <span class="text-muted">—</span>
                    @else
                        <span class="badge badge-info">{{ $channelLabel }}</span>
                    @endif
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">最終来店日</div>
                <div class="info-value">
                    {{ $member->last_visited_at ? $member->last_visited_at->format('Y/m/d') : '—' }}
                </div>
            </div>
        </div>
        @if($adminUser === null || $adminUser->hasPermission('members.manage'))
        <p class="text-muted small mb-0 mt-3">ポイント・スタンプ・クーポン・来店の操作は<a href="{{ admin_route('admin.members.edit', ['id' => $member->id]) }}">編集画面</a>から行えます。</p>
        @endif
    </div>
</div>

{{-- タグ・メモ --}}
@php $adminUser = auth('admin')->user(); $canManage = $adminUser === null || $adminUser->hasPermission('members.manage'); @endphp
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
                @forelse($member->tags as $tag)
                <span class="member-tag-item">
                    <span class="member-tag-badge" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                    @if($canManage)
                    <form action="{{ admin_route('admin.members.tags.detach', ['id' => $member->id, 'tagId' => $tag->id]) }}" method="POST" class="d-inline">
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
            <form action="{{ admin_route('admin.members.tags.attach', ['id' => $member->id]) }}" method="POST" class="member-tag-attach-form">
                @csrf
                <select name="tag_id" class="form-control form-control-sm" required>
                    <option value="">タグを付与...</option>
                    @foreach($allTags as $tag)
                    @if(!$member->tags->contains('id', $tag->id))
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
            <form action="{{ admin_route('admin.members.memo', ['id' => $member->id]) }}" method="POST">
                @csrf @method('PUT')
                <textarea name="memo" class="form-control" rows="3" maxlength="2000" placeholder="スタッフ向けのメモを入力（会員には表示されません）">{{ old('memo', $member->memo) }}</textarea>
                <div class="form-actions" style="margin-top:8px;">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> メモを保存</button>
                </div>
            </form>
            @else
            <div class="form-control-static">{{ $member->memo ?? '—' }}</div>
            @endif
        </div>
    </div>
</div>

{{-- 直近注文履歴 --}}
@if(!empty($purchaseSummary['recent_orders']) && $purchaseSummary['recent_orders']->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shopping-bag"></i> 直近の注文</h3>
        <a href="{{ admin_route('admin.orders', ['user_id' => $member->user_id]) }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-right"></i> すべての注文
        </a>
    </div>
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
            @foreach($purchaseSummary['recent_orders'] as $order)
            <tr>
                <td><a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}">{{ $order->formattedOrderNumber() }}</a></td>
                <td>¥{{ number_format($order->total_amount) }}</td>
                <td>
                    <span class="badge badge-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : ($order->status === 'cancelled' ? 'danger' : 'info')) }}">
                        {{ $order->status }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                <td class="table-actions-cell">
                    <a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i></a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@include('admin.partials.member-history.panel', [
    'section' => 'visits',
    'title' => '来店履歴',
    'icon' => 'fas fa-store',
    'placeholder' => 'メモ・商品・注文番号',
    'queryKey' => 'visits_q',
    'fromKey' => 'visits_from',
    'toKey' => 'visits_to',
    'items' => $visits,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'points',
    'title' => 'ポイント履歴',
    'icon' => 'fas fa-coins',
    'placeholder' => '説明・種別・注文番号',
    'queryKey' => 'points_q',
    'fromKey' => 'points_from',
    'toKey' => 'points_to',
    'items' => $pointTransactions,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'coupons',
    'title' => 'クーポン発行履歴',
    'icon' => 'fas fa-ticket-alt',
    'placeholder' => 'クーポン名・状態',
    'queryKey' => 'coupons_q',
    'fromKey' => 'coupons_from',
    'toKey' => 'coupons_to',
    'items' => $memberCoupons,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'redemptions',
    'title' => 'クーポン使用履歴',
    'icon' => 'fas fa-receipt',
    'placeholder' => 'クーポン名',
    'queryKey' => 'redemptions_q',
    'fromKey' => 'redemptions_from',
    'toKey' => 'redemptions_to',
    'items' => $couponRedemptions,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'rank',
    'title' => 'ランクアップ履歴',
    'icon' => 'fas fa-trophy',
    'placeholder' => 'ランク名',
    'queryKey' => 'rank_q',
    'fromKey' => 'rank_from',
    'toKey' => 'rank_to',
    'items' => $rankHistories,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

@include('admin.partials.member-history.panel', [
    'section' => 'stamps',
    'title' => 'スタンプ付与履歴',
    'icon' => 'fas fa-stamp',
    'placeholder' => 'カード名・説明',
    'queryKey' => 'stamps_q',
    'fromKey' => 'stamps_from',
    'toKey' => 'stamps_to',
    'items' => $stampTransactions,
    'member' => $member,
    'historyFilters' => $historyFilters,
])

<div class="form-actions">
    <a href="{{ admin_route('admin.members') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
@endsection

@push('scripts')
<script>
window.MemberDetailHistoryConfig = {
    memberId: {{ (int) $member->id }},
};
</script>
@endpush
