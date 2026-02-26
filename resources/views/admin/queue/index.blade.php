@extends('admin.layout')

@section('title', '順番待ち')
@section('page-title', '順番待ち')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>順番待ち</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-list-ol"></i> 順番待ち</h1>
    <p class="page-header-desc">待ち番号の管理と案内</p>
</div>

<div class="card queue-stats-card">
    <div class="card-body queue-stats-admin">
        <div class="queue-stats-admin-item">
            <span class="queue-stats-admin-label">現在の待ち人数</span>
            <span class="queue-stats-admin-value">{{ $waitingCount ?? 0 }} 組</span>
        </div>
        <div class="queue-stats-admin-item">
            <span class="queue-stats-admin-label">予測待ち時間（次のお客様）</span>
            <span class="queue-stats-admin-value">約 {{ $estimatedWaitMinutes ?? 0 }} 分</span>
        </div>
        <div class="queue-stats-admin-item queue-stats-admin-updated">
            <span class="queue-stats-admin-label">表示の更新</span>
            <span class="queue-stats-admin-value queue-stats-admin-value--time">{{ now()->format('Y/m/d H:i') }}</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">待機中一覧</h2>
    </div>
    <div class="card-body">
        @if($entries->isEmpty())
        <div class="empty-state">
            <i class="fas fa-list-ol"></i>
            <p>待機中の方はいません</p>
            <p class="empty-state-hint">順番待ちの登録があるとここに表示されます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>番号</th>
                    <th>名前</th>
                    <th>人数</th>
                    <th>LINE連携</th>
                    <th>あと</th>
                    <th>状態</th>
                    <th>登録日時</th>
                    <th>更新日時</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $e)
                @php
                    $lineUserId = $e->user?->line_user_id ?? $e->member?->user?->line_user_id;
                    $isLineLinked = is_string($lineUserId) && preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
                    $aheadCount = ($e->status === 'waiting' && $e->created_at->isToday() && isset($waitingQueueNumbers))
                        ? $waitingQueueNumbers->filter(fn($n) => $n < ($e->queue_number ?? 0))->count()
                        : null;
                @endphp
                <tr>
                    <td><strong>{{ $e->queue_number ?? $e->id }}</strong></td>
                    <td>{{ $e->member?->user?->name ?? $e->guest_name ?? '—' }}</td>
                    <td>{{ $e->party_size }}名</td>
                    <td>
                        @if($isLineLinked)
                            <span class="badge badge-success">連携済み</span>
                        @else
                            <span class="badge badge-secondary">未連携</span>
                        @endif
                    </td>
                    <td>@if($aheadCount !== null)<span class="queue-ahead-badge">あと{{ $aheadCount }}組</span>@else—@endif</td>
                    <td>
                        @if($e->status === 'waiting')<span class="badge badge-warning">待機中</span>
                        @elseif($e->status === 'called')<span class="badge badge-info">呼び出し済</span>
                        @elseif($e->status === 'entered')<span class="badge badge-success">入店済</span>
                        @else<span class="badge badge-secondary">{{ $e->status }}</span>@endif
                    </td>
                    <td>{{ $e->created_at->format('Y/m/d H:i') }}</td>
                    <td>{{ $e->updated_at->format('Y/m/d H:i') }}</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            @if($e->status === 'waiting')
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline">@csrf<input type="hidden" name="status" value="called"><button type="submit" class="btn btn-action btn-info">呼び出し</button></form>
                            @endif
                            @if($e->status === 'called')
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline">@csrf<input type="hidden" name="status" value="entered"><button type="submit" class="btn btn-action btn-success">入店</button></form>
                            @endif
                            @if(in_array($e->status, ['waiting', 'called']))
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('キャンセルしますか？');">@csrf<input type="hidden" name="status" value="cancelled"><button type="submit" class="btn btn-action btn-danger">キャンセル</button></form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $entries->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
