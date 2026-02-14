@extends('admin.layout')

@section('title', 'クーポン')
@section('page-title', 'クーポン')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>クーポン</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン</h1>
    <p class="page-header-desc">クーポンコードの作成と管理</p>
</div>

<div class="card">
    <div class="card-header card-header-with-action">
        <h2 class="card-title">クーポン一覧</h2>
        <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 作成</a>
    </div>
    <div class="card-body">
        @if($coupons->isEmpty())
        <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <p>クーポンがありません</p>
            <p class="empty-state-hint">今後、ここからクーポンを作成できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>コード</th>
                    <th>名前</th>
                    <th>種別</th>
                    <th>値</th>
                    <th>発行条件</th>
                    <th>利用回数</th>
                    <th>状態</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($coupons as $coupon)
                <tr>
                    <td><code>{{ $coupon->code }}</code></td>
                    <td>{{ $coupon->name ?? '—' }}</td>
                    <td>{{ $coupon->type === 'percent' ? '％' : '円' }}</td>
                    <td>{{ $coupon->type === 'percent' ? $coupon->value . '%' : '¥' . number_format($coupon->value) }}</td>
                    <td>
                        @if(($coupon->issue_trigger ?? 'manual') === 'first_registration')
                            <span class="badge badge-info">初回登録時</span>
                        @elseif(($coupon->issue_trigger ?? '') === 'stamp_completed')
                            <span class="badge badge-info">スタンプ達成</span>
                            @if($coupon->stampCard)
                                <small class="d-block text-muted">{{ $coupon->stampCard->name }}</small>
                            @endif
                        @else
                            <span class="badge badge-secondary">手動</span>
                        @endif
                    </td>
                    <td>{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}</td>
                    <td><span class="badge badge-{{ $coupon->is_active ? 'success' : 'danger' }}">{{ $coupon->is_active ? '有効' : '無効' }}</span></td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.coupons.edit', ['id' => $coupon->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ admin_route('admin.coupons.delete', ['id' => $coupon->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('削除しますか？');">@csrf @method('DELETE')<button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $coupons->links('vendor.pagination.admin') }}
        @endif
    </div>
</div>
@endsection
