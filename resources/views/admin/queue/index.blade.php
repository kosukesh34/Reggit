@extends('admin.layout')

@section('title', '順番待ち')
@section('page-title', '順番待ち')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>順番待ち</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-list-ol"></i> 順番待ち</h1>
    <p class="page-header-desc">待ち番号の管理と案内</p>
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
                    <th>状態</th>
                    <th>登録日時</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $e)
                <tr>
                    <td>{{ $e->queue_number ?? $e->id }}</td>
                    <td>{{ $e->member?->user?->name ?? $e->guest_name ?? '—' }}</td>
                    <td>{{ $e->party_size }}名</td>
                    <td><span class="badge badge-warning">{{ $e->status }}</span></td>
                    <td>{{ $e->created_at->format('Y/m/d H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
@endsection
