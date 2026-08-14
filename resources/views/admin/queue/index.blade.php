@extends('admin.layout')

@section('title', '順番待ち')
@section('page-title', '順番待ち')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>順番待ち</span>
</nav>

<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-list-ol"></i> 順番待ち</h1>
        <p class="page-header-desc">待ち番号の管理と案内</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.settings.queue') }}" class="btn btn-outline-secondary">
            <i class="fas fa-cog"></i> 順番待ち設定
        </a>
    </div>
</div>

<div class="queue-dashboard">
    <div class="queue-stat-hero">
        <div class="queue-stat-hero-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="queue-stat-hero-content">
            <div class="queue-stat-hero-value">{{ $view->waitingCount ?? 0 }}</div>
            <div class="queue-stat-hero-label">現在の待ち組数</div>
        </div>
    </div>
    <div class="queue-stat-hero queue-stat-hero--secondary">
        <div class="queue-stat-hero-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="queue-stat-hero-content">
            <div class="queue-stat-hero-value">{{ $view->estimatedWaitMinutes ?? 0 }}<span class="queue-stat-hero-unit">分</span></div>
            <div class="queue-stat-hero-label">予測待ち時間</div>
        </div>
    </div>
    <div class="queue-stat-updated">
        <i class="fas fa-sync-alt"></i> {{ now()->format('Y/m/d H:i') }} 更新
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">待機中一覧</h2>
    </div>
    <div class="card-body">
        @if($view->entries->isEmpty())
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
                    <th>登録チャネル</th>
                    <th>待ち順</th>
                    <th>状態</th>
                    <th>受付時刻</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($view->entries as $e)
                @php
                    $channelUser = $e->user ?? $e->member?->user;
                    $channelLabel = \App\Support\MemberChannel::label($channelUser);
                    $aheadCount = ($e->status === 'waiting' && $e->created_at->gte(now()->subDay()) && isset($view->waitingQueueNumbers))
                        ? $view->waitingQueueNumbers->filter(fn($n) => $n < ($e->queue_number ?? 0))->count()
                        : null;
                @endphp
                <tr>
                    <td><span class="queue-number-badge">{{ $e->formattedQueueNumber() }}</span></td>
                    <td>
                        @php
                            $member = $e->member ?? $e->user?->member;
                            $displayName = $member?->user?->name ?? $e->user?->name ?? $e->guest_name ?? '—';
                        @endphp
                        @if($member !== null)
                            <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}">{{ $displayName }}</a>
                        @else
                            {{ $displayName }}
                        @endif
                    </td>
                    <td><strong>{{ $e->party_size }}</strong>名</td>
                    <td>
                        @if($channelLabel === '—')
                            <span class="text-muted">—</span>
                        @else
                            <span class="badge badge-info">{{ $channelLabel }}</span>
                        @endif
                    </td>
                    <td>
                        @if($aheadCount !== null)
                            <span class="queue-ahead-badge">あと{{ $aheadCount }}組</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($e->status === 'waiting')
                            <span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> 待機中</span>
                        @elseif($e->status === 'called')
                            <span class="badge badge-info"><i class="fas fa-bullhorn"></i> 呼び出し済</span>
                        @elseif($e->status === 'entered')
                            <span class="badge badge-success"><i class="fas fa-door-open"></i> 入店済</span>
                        @elseif($e->status === 'cancelled')
                            <span class="badge badge-danger"><i class="fas fa-times"></i> キャンセル</span>
                        @else
                            <span class="badge badge-secondary">{{ $e->status }}</span>
                        @endif
                    </td>
                    <td>
                        <div>{{ $e->created_at->format('H:i') }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $e->created_at->format('m/d') }}</div>
                    </td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            @if($e->status === 'waiting')
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="called">
                                <button type="submit" class="btn btn-action btn-info"><i class="fas fa-bullhorn"></i> 呼び出し</button>
                            </form>
                            @endif
                            @if($e->status === 'called')
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="called">
                                <button type="submit" class="btn btn-action btn-info"><i class="fas fa-bullhorn"></i> 再呼び出し</button>
                            </form>
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="entered">
                                <button type="submit" class="btn btn-action btn-success"><i class="fas fa-door-open"></i> 入店</button>
                            </form>
                            @endif
                            @if(in_array($e->status, ['waiting', 'called']))
                            <form action="{{ admin_route('admin.queue.status', ['id' => $e->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('キャンセルしますか？');">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-times"></i> キャンセル</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $view->entries->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
