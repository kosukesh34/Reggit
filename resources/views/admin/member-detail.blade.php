@extends('admin.layout')

@section('title', '会員詳細')
@section('page-title', '会員詳細 #' . $member->id)

@section('content')
@if(session('success'))
<div class="alert alert-success" role="alert">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user"></i> 会員情報</h3>
        <a href="{{ admin_route('admin.members') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
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
