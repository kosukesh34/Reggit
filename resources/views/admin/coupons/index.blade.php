@extends('admin.layout')

@section('title', 'クーポン')
@section('page-title', 'クーポン')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>クーポン</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン</h1>
    <p class="page-header-desc">クーポンコードの作成と管理</p>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">クーポン一覧</h2>
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
                    <th>利用回数</th>
                    <th>状態</th>
                </tr>
            </thead>
            <tbody>
                @foreach($coupons as $coupon)
                <tr>
                    <td><code>{{ $coupon->code }}</code></td>
                    <td>{{ $coupon->name ?? '—' }}</td>
                    <td>{{ $coupon->type === 'percent' ? '％' : '円' }}</td>
                    <td>{{ $coupon->type === 'percent' ? $coupon->value . '%' : '¥' . number_format($coupon->value) }}</td>
                    <td>{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}</td>
                    <td><span class="badge badge-{{ $coupon->is_active ? 'success' : 'danger' }}">{{ $coupon->is_active ? '有効' : '無効' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $coupons->links() }}</div>
        @endif
    </div>
</div>
@endsection
