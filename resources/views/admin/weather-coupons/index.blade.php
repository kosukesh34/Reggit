@extends('admin.layout')
@section('title', '雨の日の判定結果')
@section('page-title', '雨の日の判定結果')
@section('content')
@php $canManage = auth('admin')->user()?->hasPermission('announcements.manage'); @endphp
<nav class="breadcrumb" aria-label="パンくず"><a href="{{ admin_route('admin.after-registration-deliveries.index') }}">スケジュール配信</a><span class="breadcrumb-sep">/</span><span>雨の日の判定結果</span></nav>
<div class="card">
    <div class="card-header"><h2 class="card-title"><i class="fas fa-cloud-rain"></i> 最近の判定結果</h2></div>
    <div class="card-body">
        <p class="form-text">雨の日の配信は「スケジュール配信」で設定します。承認待ちの配信は、期限までに承認しないと送りません。「今日は配らない」を選ぶと、その日は判定も承認のメールも止まります。</p>
        @if($runs->isEmpty())
            <p>まだ判定結果はありません。</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>営業日</th><th>店舗</th><th>配信</th><th>状態</th><th>最大降水確率</th><th>送った人数</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach($runs as $run)
                        @php
                            $pending = in_array($run->status, [\App\Models\WeatherCouponRun::STATUS_AWAITING_APPROVAL, \App\Models\WeatherCouponRun::STATUS_EXPIRED], true);
                            $awaiting = $run->status === \App\Models\WeatherCouponRun::STATUS_AWAITING_APPROVAL && $run->approval_expires_at?->isFuture();
                            $status = $pending && ! $awaiting ? \App\Models\WeatherCouponRun::STATUS_EXPIRED : $run->status;
                            $delivery = $run->delivery;
                        @endphp
                        <tr>
                            <td>{{ $run->business_date->format('Y/m/d') }}</td>
                            <td>{{ $delivery?->weatherStore?->name }}</td>
                            <td>{{ trim((string) $delivery?->title) !== '' ? $delivery->title : $delivery?->coupon?->name }}</td>
                            <td>
                                {{ \App\Models\WeatherCouponRun::STATUS_LABELS[$status] ?? $status }}
                                @if($awaiting)<br><small>{{ $run->approval_expires_at->format('H:i') }} まで</small>@endif
                            </td>
                            <td>{{ $run->max_precipitation_probability !== null ? $run->max_precipitation_probability.'%' : '—' }}</td>
                            <td>{{ number_format($run->issued_count) }}人</td>
                            <td>
                                @if($canManage && $awaiting)
                                    <form method="POST" action="{{ admin_route('admin.weather-coupons.approve', ['id' => $run->id]) }}" style="display:inline">@csrf<button class="btn btn-primary btn-sm">承認して送る</button></form>
                                @endif
                                @if($canManage && $pending)
                                    <form method="POST" action="{{ admin_route('admin.weather-coupons.decline', ['id' => $run->id]) }}" style="display:inline">@csrf<button class="btn btn-secondary btn-sm">今日は配らない</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
