@extends('admin.layout')

@section('title', 'お知らせ')
@section('page-title', 'お知らせ')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>お知らせ</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせ</h1>
    <p class="page-header-desc">お客様へのお知らせの管理</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">お知らせ一覧</h2>
    </div>
    <div class="card-body">
        @if($announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <p>お知らせがありません</p>
            <p class="empty-state-hint">今後、ここからお知らせを登録できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>タイトル</th>
                    <th>公開日</th>
                    <th>掲載終了</th>
                    <th>固定</th>
                </tr>
            </thead>
            <tbody>
                @foreach($announcements as $a)
                <tr>
                    <td>{{ Str::limit($a->title, 40) }}</td>
                    <td>{{ $a->published_at ? $a->published_at->format('Y/m/d') : '—' }}</td>
                    <td>{{ $a->expires_at ? $a->expires_at->format('Y/m/d') : '—' }}</td>
                    <td>@if($a->is_pinned)<i class="fas fa-thumbtack"></i>@else—@endif</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $announcements->links() }}</div>
        @endif
    </div>
</div>
@endsection
