@extends('admin.layout')

@section('title', 'クーポン')
@section('page-title', 'クーポン')
@section('page-kind', 'coupon-index')

@section('content')
<div class="coupon-admin">
@if(session('warning'))
<div class="alert alert-warning" role="alert">
    <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
</div>
@endif
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>クーポン</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン</h1>
</div>

<div class="card cp-list-card">
    <div class="card-header card-header-with-action">
        <h2 class="card-title">クーポン一覧</h2>
        <div class="table-actions">
            <a href="{{ admin_route('admin.coupons.bulk-issue') }}" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> クーポンを配信</a>
            <a href="{{ admin_route('admin.coupons.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 作成</a>
        </div>
    </div>
    <div class="card-body">
        @if($coupons->isEmpty())
        <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <p>クーポンがありません</p>
            <p class="empty-state-hint">「作成」からクーポンを追加できます</p>
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>コード</th>
                    <th>名前</th>
                    <th>種別</th>
                    <th>値</th>
                    <th>利用回数</th>
                    <th>スマレジ</th>
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
                        <a href="{{ admin_route('admin.coupons.redemptions', ['id' => $coupon->id]) }}">{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}</a>
                    </td>
                    <td>
                        @if($coupon->smaregi_coupon_id)
                            <span class="badge badge-success" title="ID: {{ $coupon->smaregi_coupon_id }}">連携済</span>
                        @else
                            <span class="badge badge-secondary">未連携</span>
                            <a href="{{ admin_route('admin.coupons.bulk-issue') }}#smaregi-sync" class="d-block small mt-1">スマレジへ反映</a>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $coupon->is_active ? 'success' : 'danger' }}">{{ $coupon->is_active ? '有効' : '無効' }}</span></td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.coupons.bulk-issue', ['coupon_id' => $coupon->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-paper-plane"></i> 配布</a>
                            <a href="{{ admin_route('admin.coupons.redemptions', ['id' => $coupon->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-receipt"></i> 利用履歴</a>
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

<div class="card cp-list-card">
    <div class="card-header">
        <h2 class="card-title">配布履歴</h2>
    </div>
    <div class="card-body">
        @if(($deliveryHistories ?? []) === [])
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <p>まだ配布履歴がありません</p>
            <p class="empty-state-hint">「クーポンを配信」から配布すると、ここに履歴が表示されます</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>クーポン</th>
                        <th>配信種別</th>
                        <th>配信対象</th>
                        <th>状態</th>
                        <th>発行数</th>
                        <th class="table-actions-cell">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveryHistories as $history)
                    <tr>
                        <td>{{ $history['timing_label'] }}</td>
                        <td>
                            <strong>{{ $history['coupon_name'] }}</strong>
                            <span class="d-block text-muted small"><code>{{ $history['coupon_code'] }}</code></span>
                        </td>
                        <td>{{ $history['delivery_type_label'] }}</td>
                        <td title="{{ $history['recipients_title'] }}">{{ $history['recipients_label'] }}</td>
                        <td><span class="badge badge-{{ $history['status_badge'] }}">{{ $history['status_label'] }}</span></td>
                        <td>{{ $history['issued_count_label'] }}</td>
                        <td class="table-actions-cell">
                            <a href="{{ admin_route('admin.coupons.deliveries.show', ['id' => $history['id']]) }}" class="btn btn-action btn-edit"><i class="fas fa-eye"></i> 詳細</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ (int) @filemtime(public_path('css/admin/coupons.css')) }}">
@endpush
@endsection
