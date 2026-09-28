@extends('admin.layout')
@section('title', 'クーポン詳細')
@section('page-title', 'クーポン詳細')
@section('page-kind', 'coupon-show')
@section('content')
<div class="coupon-admin">
    <nav class="breadcrumb" aria-label="パンくず"><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>{{ $coupon->name ?? $coupon->code }}</span></nav>
    <div class="page-header-row">
        <div class="page-header"><h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> {{ $coupon->name ?? $coupon->code }}</h1><p class="page-header-desc">コード: {{ $coupon->code }}</p></div>
        <div class="table-actions"><a href="{{ admin_route('admin.coupons.redemptions', ['id' => $coupon->id]) }}" class="btn btn-secondary">利用履歴</a>@if(auth('admin')->user()?->hasPermission('coupons.manage'))<a href="{{ admin_route('admin.coupons.edit', ['id' => $coupon->id]) }}" class="btn btn-primary">編集</a>@endif</div>
    </div>
    <div class="cp-detail-stats">
        <div class="card"><div class="card-body"><span>配布回数</span><strong>{{ number_format($stats['delivery_count']) }}</strong><small>配布操作・予約の件数</small></div></div>
        <div class="card"><div class="card-body"><span>発行枚数</span><strong>{{ number_format($stats['issued_count']) }}</strong><small>会員への発行総数</small></div></div>
        <div class="card"><div class="card-body"><span>未使用</span><strong>{{ number_format($stats['unused_count']) }}</strong><small>発行済みの未使用枚数</small></div></div>
        <div class="card"><div class="card-body"><span>利用回数</span><strong>{{ number_format($stats['redeemed_count']) }}</strong><small>記録された利用件数</small></div></div>
    </div>
    <div class="cp-detail-layout">
        <div class="card cp-list-card cp-display-card">
            <div class="card-header"><h2 class="card-title">クーポン</h2></div>
            @if($coupon->image_url)<img class="cp-display-image" src="{{ $coupon->image_url }}" alt="{{ $coupon->name ?? $coupon->code }}の画像">@endif
            <div class="card-body"><p class="cp-display-benefit">{{ $coupon->type === 'percent' ? rtrim(rtrim((string) $coupon->value, '0'), '.').'％ OFF' : number_format((float) $coupon->value).'円 OFF' }}</p>@if($coupon->description)<p>{{ $coupon->description }}</p>@endif</div>
        </div>
        <div class="card cp-list-card">
            <div class="card-header"><h2 class="card-title">設定内容</h2></div>
            <div class="card-body"><dl class="cp-detail-list">
                <dt>状態</dt><dd><span class="badge badge-{{ $coupon->is_active ? 'success' : 'danger' }}">{{ $coupon->is_active ? '有効' : '無効' }}</span></dd>
                <dt>利用期間</dt><dd>{{ $coupon->start_at?->format('Y/m/d') ?? '指定なし' }} ～ {{ $coupon->end_at?->format('Y/m/d') ?? '無期限' }}</dd>
                <dt>発行条件</dt><dd>{{ \App\Models\Coupon::getIssueTriggerOptions()[$coupon->issue_trigger] ?? '—' }}</dd>
                <dt>最低注文金額</dt><dd>{{ (float) $coupon->min_order_amount > 0 ? '¥'.number_format((float) $coupon->min_order_amount) : '指定なし' }}</dd>
                <dt>最高注文金額</dt><dd>{{ $coupon->max_order_amount !== null ? '¥'.number_format((float) $coupon->max_order_amount) : '指定なし' }}</dd>
                <dt>適用条件</dt><dd>{{ \App\Enums\CouponApplyConditionDivision::getValues()[$coupon->apply_condition_division] ?? '—' }}</dd>
                <dt>併用</dt><dd>{{ \App\Enums\CouponCombineDivision::getValues()[$coupon->combine_division] ?? '—' }}</dd>
                <dt>利用上限</dt><dd>{{ $coupon->usage_limit !== null ? number_format($coupon->usage_limit).'回' : '制限なし' }}</dd>
                <dt>スマレジ</dt><dd>{{ $coupon->smaregi_coupon_id ? '連携済み（ID: '.$coupon->smaregi_coupon_id.'）' : '未連携' }}</dd>
                <dt>作成日時</dt><dd>{{ $coupon->created_at?->format('Y/m/d H:i') ?? '—' }}</dd>
            </dl></div>
        </div>
    </div>
    <div class="card cp-list-card">
        <div class="card-header"><h2 class="card-title">配布履歴</h2></div>
        <div class="card-body">
            @if($deliveries->isEmpty())<p class="text-muted mb-0">配布履歴はありません。</p>
            @else
                <div class="table-responsive"><table class="table">
                    <thead><tr><th>日時</th><th>配布種別</th><th>状態</th><th>発行数</th><th>操作</th></tr></thead>
                    <tbody>@foreach($deliveries as $delivery)<tr>
                        <td>{{ ($delivery->sent_at ?? $delivery->scheduled_at ?? $delivery->created_at)?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td>{{ \App\Enums\CouponDeliveryType::getValues()[$delivery->delivery_type] ?? $delivery->delivery_type }}</td>
                        <td>{{ \App\Enums\CouponDeliveryStatus::getValues()[$delivery->status] ?? $delivery->status }}</td>
                        <td>{{ number_format($delivery->issued_count) }}人</td>
                        <td>@if(auth('admin')->user()?->hasPermission('coupons.manage'))<a href="{{ admin_route('admin.coupons.deliveries.show', ['id' => $delivery->id]) }}">詳細</a>@else — @endif</td>
                    </tr>@endforeach</tbody>
                </table></div>
                {{ $deliveries->links('vendor.pagination.admin') }}
            @endif
        </div>
    </div>
</div>
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ (int) @filemtime(public_path('css/admin/coupons.css')) }}">@endpush
@endsection
