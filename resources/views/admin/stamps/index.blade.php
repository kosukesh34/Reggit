@extends('admin.layout')

@section('title', 'スタンプ')
@section('page-title', 'スタンプ')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>スタンプ</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-stamp"></i> スタンプ</h1>
    <p class="page-header-desc">スタンプカードの設定と管理</p>
</div>

<div class="card">
    <div class="card-header card-header-with-action">
        <h2 class="card-title">スタンプカード一覧</h2>
        <a href="{{ admin_route('admin.stamps.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</a>
    </div>
    <div class="card-body">
        @if($stampCards->isEmpty())
        <div class="empty-state">
            <i class="fas fa-stamp"></i>
            <p>スタンプカードがありません</p>
            <p class="empty-state-hint"><a href="{{ admin_route('admin.stamps.create') }}">追加</a>からスタンプカードを作成できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>必要数</th>
                    <th>特典</th>
                    <th>状態</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stampCards as $card)
                <tr>
                    <td>{{ $card->name }}</td>
                    <td>{{ $card->required_stamps }}個</td>
                    <td>
                        @php($milestones = is_array($card->reward_milestones ?? null) ? $card->reward_milestones : [])
                        @if($milestones !== [])
                            @foreach($milestones as $milestone)
                                @if(is_array($milestone) && isset($milestone['stamp_count'], $milestone['description']) && (string)($milestone['description'] ?? '') !== '')
                                    <div>{{ $milestone['stamp_count'] }}個: {{ $milestone['description'] }}</div>
                                @endif
                            @endforeach
                            @if($card->reward_description)
                                <div class="text-muted">補足: {{ $card->reward_description }}</div>
                            @endif
                        @else
                            {{ $card->reward_description ?? '—' }}
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $card->is_active ? 'success' : 'danger' }}">{{ $card->is_active ? '有効' : '無効' }}</span></td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            @if($card->is_active)
                            <a href="{{ admin_route('admin.stamps.grant-qr', ['id' => $card->id]) }}" class="btn btn-action btn-outline"><i class="fas fa-qrcode"></i> 付与QR</a>
                            @endif
                            <a href="{{ admin_route('admin.stamps.edit', ['id' => $card->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ admin_route('admin.stamps.delete', ['id' => $card->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('削除しますか？');">@csrf @method('DELETE')<button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $stampCards->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
