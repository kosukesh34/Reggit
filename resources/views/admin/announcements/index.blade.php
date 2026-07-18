@extends('admin.layout')

@section('title', 'お知らせ')
@section('page-title', 'お知らせ')

@section('content')
<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせ</h1>
        <p class="page-header-desc">お客様へのお知らせの管理</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.announcements.delivery.create') }}" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> 配信</a>
        <a href="{{ admin_route('admin.announcements.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">お知らせ一覧</h3>
        @if(!$announcements->isEmpty())
        <span class="card-header-note">{{ $announcements->total() }}件</span>
        @endif
    </div>
    <div class="card-body">
        @if($announcements->isEmpty())
        <div class="empty-state">
            <i class="fas fa-bullhorn"></i>
            <p>お知らせがありません</p>
            <p class="empty-state-hint">「追加」ボタンからお知らせを登録できます</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>タイトル</th>
                        <th>公開日</th>
                        <th>掲載終了</th>
                        <th>固定</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($announcements as $a)
                    <tr>
                        <td><strong>{{ Str::limit($a->title, 40) }}</strong></td>
                        <td>{{ $a->published_at ? $a->published_at->format('Y/m/d') : '—' }}</td>
                        <td>{{ $a->expires_at ? $a->expires_at->format('Y/m/d') : '—' }}</td>
                        <td>@if($a->is_pinned)<span class="badge badge-info"><i class="fas fa-thumbtack"></i> 固定</span>@else<span class="text-muted">—</span>@endif</td>
                        <td class="table-actions-cell">
                            <div class="table-actions">
                                <a href="{{ admin_route('admin.announcements.edit', ['id' => $a->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                                <form action="{{ admin_route('admin.announcements.delete', ['id' => $a->id]) }}" method="POST" class="d-inline delete-form" onsubmit="return confirm('削除しますか？');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-action btn-danger no-loading"><i class="fas fa-trash"></i> 削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $announcements->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
