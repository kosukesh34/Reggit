@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細 #' . $member->id)

@section('content')
@if(session('success'))
<div class="alert alert-success" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-error" role="alert">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

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

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store"></i> 来店履歴</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>来店日時</th>
                <th>経路</th>
                <th>購入額</th>
                <th>購入商品</th>
                <th>注文番号</th>
                <th>メモ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->visits as $visit)
            @php
                $visitDetailUrl = admin_route('admin.members.visits.detail', ['id' => $member->id, 'visitId' => $visit->id]);
                $orderDetailUrl = $visit->order !== null
                    ? admin_route('admin.orders.detail', ['id' => $visit->order->id])
                    : null;
                $rowHref = $orderDetailUrl ?? $visitDetailUrl;
                $productSummary = $visit->items->take(3)->pluck('product_name')->filter()->implode('、');
                if ($visit->items->count() > 3) {
                    $productSummary .= ' 他'.($visit->items->count() - 3).'点';
                }
            @endphp
            <tr class="clickable-row" onclick="window.location='{{ $rowHref }}'" style="cursor:pointer;">
                <td>{{ $visit->visited_at->format('Y/m/d H:i') }}</td>
                <td>{{ \App\Enums\MemberVisitSource::getLabel($visit->source) }}</td>
                <td>
                    @if($visit->amount !== null)
                        ¥{{ number_format($visit->amount) }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $productSummary !== '' ? $productSummary : '—' }}</td>
                <td>
                    @if($visit->order !== null)
                        {{ $visit->order->formattedOrderNumber() }}
                    @elseif($visit->smaregi_transaction_head_id !== null)
                        #{{ $visit->smaregi_transaction_head_id }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $visit->note ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="fas fa-store"></i>
                    <p>来店履歴がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-coins"></i> ポイント履歴</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>日時</th>
                <th>種類</th>
                <th>ポイント</th>
                <th>説明</th>
                <th>注文番号</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->pointTransactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
                <td>
                    @php
                        $typeLabel = \App\Enums\PointTransactionType::getLabel($transaction->type);
                        $typeBadge = $transaction->type === \App\Enums\PointTransactionType::EARNED || $transaction->type === \App\Enums\PointTransactionType::REFUNDED
                            ? 'success'
                            : 'danger';
                    @endphp
                    <span class="badge badge-{{ $typeBadge }}">{{ $typeLabel }}</span>
                </td>
                <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}pt</td>
                <td>{{ $transaction->description ?? 'N/A' }}</td>
                <td>{{ $transaction->order !== null ? $transaction->order->formattedOrderNumber() : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-coins"></i>
                    <p>ポイント履歴がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ticket-alt"></i> クーポン発行履歴</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>クーポン名</th>
                <th>状態</th>
                <th>発行日</th>
                <th>有効期限</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->memberCoupons as $mc)
            <tr>
                <td>{{ $mc->coupon_name_snapshot ?? $mc->coupon->name ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ $mc->status === 'unused' ? 'success' : 'secondary' }}">
                        {{ $mc->status === 'unused' ? '未使用' : '使用済み' }}
                    </span>
                </td>
                <td>{{ $mc->issued_at ? $mc->issued_at->format('Y/m/d H:i') : '—' }}</td>
                <td>{{ $mc->valid_until ? $mc->valid_until->format('Y/m/d H:i') : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <p>クーポン発行履歴がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-stamp"></i> スタンプ付与履歴</h3>
    </div>
    @if($member->memberStamps->isNotEmpty())
    <div class="card-body pb-0">
        <p class="text-muted small mb-2">現在の状況:
            @foreach($member->memberStamps as $ms)
                {{ $ms->stampCard->name ?? '—' }} {{ $ms->current_stamps }} / {{ $ms->stampCard->required_stamps ?? 0 }}{{ $ms->completed_at ? '（達成済み）' : '' }}@if(!$loop->last)、@endif
            @endforeach
        </p>
    </div>
    @endif
    <table class="table">
        <thead>
            <tr>
                <th>日時</th>
                <th>スタンプカード</th>
                <th>増減</th>
                <th>付与後残高</th>
                <th>説明</th>
            </tr>
        </thead>
        <tbody>
            @forelse($member->stampTransactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('Y/m/d H:i') }}</td>
                <td>{{ $tx->stampCard->name ?? '—' }}</td>
                <td>{{ $tx->delta > 0 ? '+' : '' }}{{ $tx->delta }}</td>
                <td>{{ $tx->balance_after }}</td>
                <td>{{ $tx->description ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-stamp"></i>
                    <p>スタンプ付与履歴がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="form-actions">
    <a href="{{ admin_route('admin.members') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
@endsection
