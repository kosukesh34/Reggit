@extends('admin.layout')
@section('title', 'クーポン')
@section('page-title', 'クーポン')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.coupons') }}">クーポン</a><span class="breadcrumb-sep">/</span><span>クーポン</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-ticket-alt"></i> クーポン</h1>
</div>
<div>
    <div class="card">
        <div class="card-body">
            <p>クーポン機能：<strong>{{ $featureCouponsEnabled ? '有効' : '無効' }}</strong>　<a href="{{ admin_route('admin.settings.features') }}">利用機能で変更</a></p>
            <p>クーポンの既定画像は <a href="{{ admin_route('admin.settings.app-branding') }}">アプリデザインの店舗名・画像</a> で変更できます。</p>
            <div class="form-actions"><a href="{{ admin_route('admin.coupons') }}" class="btn btn-secondary">クーポン一覧へ</a></div>
        </div>
    </div>
</div>
@endsection
