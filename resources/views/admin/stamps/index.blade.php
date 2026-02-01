@extends('admin.layout')

@section('title', 'スタンプ')
@section('page-title', 'スタンプ')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>スタンプ</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-stamp"></i> スタンプ</h1>
    <p class="page-header-desc">スタンプカードの設定と管理</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">スタンプカード一覧</h2>
    </div>
    <div class="card-body">
        @if($stampCards->isEmpty())
        <div class="empty-state">
            <i class="fas fa-stamp"></i>
            <p>スタンプカードがありません</p>
            <p class="empty-state-hint">今後、ここからスタンプカードを追加できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>必要数</th>
                    <th>特典</th>
                    <th>状態</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stampCards as $card)
                <tr>
                    <td>{{ $card->name }}</td>
                    <td>{{ $card->required_stamps }}個</td>
                    <td>{{ $card->reward_description ?? '—' }}</td>
                    <td><span class="badge badge-{{ $card->is_active ? 'success' : 'danger' }}">{{ $card->is_active ? '有効' : '無効' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $stampCards->links() }}</div>
        @endif
    </div>
</div>
@endsection
