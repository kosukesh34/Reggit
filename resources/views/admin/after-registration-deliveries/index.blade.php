@extends('admin.layout')

@section('title', '登録後配信')
@section('page-title', '登録後配信')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>登録後配信</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-clock"></i> 登録後配信</h1>
    <p class="page-header-desc">会員登録から指定分数後に、お知らせとクーポンを自動で配信します</p>
</div>

@if(session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header card-header-with-action">
        <h2 class="card-title">一覧</h2>
        <div class="table-actions">
            <a href="{{ admin_route('admin.after-registration-deliveries.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
        </div>
    </div>
    <div class="card-body">
        @if($deliveries->isEmpty())
        <div class="empty-state">
            <i class="fas fa-clock"></i>
            <p>登録後配信がありません</p>
            <p class="empty-state-hint">「追加」から、会員登録から何分後に配信するかを設定できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>配信タイミング</th>
                    <th>タイトル</th>
                    <th>クーポン</th>
                    <th>状態</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveries as $d)
                <tr>
                    <td>登録から {{ $d->delay_minutes }} 分後</td>
                    <td>{{ Str::limit($d->title, 40) }}</td>
                    <td>{{ $d->coupon ? $d->coupon->name : '—' }}</td>
                    <td>@if($d->is_active)<span class="badge badge-success">有効</span>@else<span class="badge badge-secondary">無効</span>@endif</td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.after-registration-deliveries.edit', ['id' => $d->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ admin_route('admin.after-registration-deliveries.destroy', ['id' => $d->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('削除しますか？');">@csrf @method('DELETE')<button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
