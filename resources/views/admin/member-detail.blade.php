@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細 #' . $member->id)

@section('content')
@if(session('success'))
<div class="alert alert-success" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
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
        </div>
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
                <td><a href="{{ admin_route('admin.orders.detail', ['id' => $order->id]) }}">{{ $order->order_number }}</a></td>
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
                    <span class="badge badge-{{ $transaction->type === 'earned' ? 'success' : 'danger' }}">
                        {{ $transaction->type === 'earned' ? '獲得' : '使用' }}
                    </span>
                </td>
                <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format($transaction->points) }}pt</td>
                <td>{{ $transaction->description ?? 'N/A' }}</td>
                <td>{{ $transaction->order->order_number ?? 'N/A' }}</td>
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
        <h3 class="card-title"><i class="fas fa-ticket-alt"></i> クーポン配布</h3>
    </div>
    <div class="card-body">
        @if($activeCoupons->isEmpty())
            <p class="text-muted">配布可能なクーポンがありません。<a href="{{ admin_route('admin.coupons.create') }}">クーポンを作成</a>してください。</p>
        @else
            <form action="{{ admin_route('admin.members.issue-coupon', ['id' => $member->id]) }}" method="POST" class="form-inline mb-0">
                @csrf
                <div class="form-group me-2">
                    <label for="coupon_id" class="form-label me-1">クーポン</label>
                    <select name="coupon_id" id="coupon_id" class="form-control" required>
                        <option value="">選択してください</option>
                        @foreach($activeCoupons as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}（{{ $c->code }}）</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-gift"></i> 配布する</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-stamp"></i> スタンプ付与</h3>
    </div>
    <div class="card-body">
        @if($activeStampCards->isEmpty())
            <p class="text-muted">スタンプカードがありません。<a href="{{ admin_route('admin.stamps.create') }}">スタンプカードを作成</a>してください。</p>
        @else
            <form action="{{ admin_route('admin.members.add-stamps', ['id' => $member->id]) }}" method="POST" class="form-inline mb-0">
                @csrf
                <div class="form-group me-2">
                    <label for="stamp_card_id" class="form-label me-1">スタンプカード</label>
                    <select name="stamp_card_id" id="stamp_card_id" class="form-control" required>
                        <option value="">選択してください</option>
                        @foreach($activeStampCards as $sc)
                            <option value="{{ $sc->id }}">{{ $sc->name }}（{{ $sc->required_stamps }}個で達成）</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group me-2">
                    <label for="stamp_count" class="form-label me-1">付与数</label>
                    <input type="number" name="count" id="stamp_count" class="form-control" value="1" min="1" max="100" style="width:5em">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> 付与する</button>
            </form>
        @endif
    </div>
</div>

@if($member->memberStamps->isNotEmpty() || $member->memberCoupons->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history"></i> スタンプ・クーポン状況</h3>
    </div>
    <div class="card-body">
        @if($member->memberStamps->isNotEmpty())
            <h4 class="h6 mt-0"><i class="fas fa-stamp"></i> スタンプ</h4>
            <ul class="list-unstyled mb-3">
                @foreach($member->memberStamps as $ms)
                    <li>{{ $ms->stampCard->name ?? '—' }}: {{ $ms->current_stamps }} / {{ $ms->stampCard->required_stamps ?? 0 }} {{ $ms->completed_at ? '（達成済み）' : '' }}</li>
                @endforeach
            </ul>
        @endif
        @if($member->memberCoupons->isNotEmpty())
            <h4 class="h6"><i class="fas fa-ticket-alt"></i> 保有クーポン</h4>
            <ul class="list-unstyled mb-0">
                @foreach($member->memberCoupons as $mc)
                    <li>{{ $mc->coupon_name_snapshot ?? $mc->coupon->name ?? '—' }} — {{ $mc->status === 'unused' ? '未使用' : '使用済み' }}（発行: {{ $mc->issued_at->format('Y/m/d') }}）</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endif

<div class="form-actions">
    <a href="{{ admin_route('admin.members') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> 戻る
    </a>
</div>
@endsection
