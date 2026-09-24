@extends('admin.layout')

@section('title', '分析から配信')
@section('page-title', '分析から配信')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.analytics') }}">分析</a>
    <span class="breadcrumb-sep">/</span>
    <span>配信</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-paper-plane"></i> 分析から配信</h1>
</div>

<div class="card mb-4">
    <div class="card-header">分析AI向けAPI</div>
    <div class="card-body">
        <p>店舗のAPIトークン（Bearer）で呼び出します。返すのは期間の集計だけで、会員個人の情報は含みません。配信APIは全員への即時クーポン配布だけを実行します。</p>
        <p><code>GET /api/v1/{{ $projectKey }}/analysis/summary?start=2026-09-01&amp;end=2026-09-24</code></p>
        <p><code>POST /api/v1/{{ $projectKey }}/analysis/deliveries</code></p>
        <p class="form-text">本文: <code>{"coupon_id": 1, "send_notification": true}</code></p>
    </div>
</div>

<div class="card">
    <div class="card-header">店舗から配信を実行</div>
    <div class="card-body">
        <form action="{{ admin_route('admin.analysis-deliveries.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="coupon_id" class="form-label">クーポン</label>
                <select name="coupon_id" id="coupon_id" class="form-control" required>
                    <option value="">選択してください</option>
                    @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id }}" @selected((int) old('coupon_id') === (int) $coupon->id)>{{ $coupon->name }}</option>
                    @endforeach
                </select>
                @error('coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="send_notification" value="1" checked>
                    通知を送る
                </label>
            </div>
            <button type="submit" class="btn btn-primary">全員に今すぐ配信</button>
        </form>
    </div>
</div>
@endsection
