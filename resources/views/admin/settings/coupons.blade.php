@extends('admin.layout')
@section('title', 'クーポン設定')
@section('page-title', 'クーポン設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>クーポン設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン設定</h1>
</div>
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
<form action="{{ admin_route('admin.settings.coupons.update') }}" method="POST" enctype="multipart/form-data">
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
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'coupon_default_image',
                'logoClearName' => 'coupon_default_image_clear',
                'logoFieldId' => 'coupon_default_image',
                'logoCurrentUrl' => (string) old('coupon_default_image_url', $couponDefaultImageUrl),
                'logoLabel' => 'クーポン用デフォルト画像',
                'logoHelp' => 'クーポンに画像を指定していない場合に表示する画像です。',
            ])
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">クーポン一覧へ</a></div>
        </div>
    </div>
</form>
@endsection
