@extends('admin.layout')
@section('title', 'クーポン設定')
@section('page-title', 'クーポン設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>クーポン設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン設定</h1>
    <p class="page-header-desc">クーポンのデフォルト表示や機能のON/OFFを設定します。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'coupons'])
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
<form action="{{ admin_route('admin.settings.coupons.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">クーポン機能</label>
                <label class="checkbox-label">
                    <input type="hidden" name="feature_coupons_enabled" value="0">
                    <input type="checkbox" name="feature_coupons_enabled" value="1" {{ $featureCouponsEnabled ? 'checked' : '' }}>
                    <span>クーポン機能を有効にする</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">クーポン用デフォルト画像URL</label>
                <input type="url" name="coupon_default_image_url" class="form-control" value="{{ old('coupon_default_image_url', $couponDefaultImageUrl) }}" maxlength="500" placeholder="https://...">
                <small class="form-text text-muted">クーポンに画像を指定していない場合に表示する画像のURLです。</small>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">クーポン一覧へ</a></div>
        </div>
    </div>
</form>
@endsection
