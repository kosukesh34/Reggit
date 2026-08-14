@extends('admin.layout')
@section('title', 'クーポン作成')
@section('page-title', 'クーポン作成')
@section('page-kind', 'coupon-create')
@section('content')
@php $returnTo = $returnTo ?? null; @endphp
<div class="coupon-admin">
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    @if($returnTo === 'bulk-issue')
        <a href="{{ admin_route('admin.coupons.bulk-issue') }}">配布</a>
        <span class="breadcrumb-sep">/</span>
    @endif
    <span>新規</span>
</nav>
<header class="cp-hero">
    <div class="cp-hero-copy">
        <p class="cp-hero-eyebrow"><span></span> NEW COUPON</p>
        <h1>また来たくなるきっかけを、<br>ひとつのクーポンに。</h1>
        <p>特典内容と配布タイミングを設定すると、会員アプリでの見え方がその場で完成します。</p>
    </div>
    <div class="cp-hero-meta" aria-label="作成のポイント">
        <div>
            <span class="cp-hero-meta-icon"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i></span>
            <span><strong>かんたん作成</strong>目安 3分</span>
        </div>
        <div>
            <span class="cp-hero-meta-icon"><i class="fas fa-mobile-screen" aria-hidden="true"></i></span>
            <span><strong>ライブプレビュー</strong>入力内容をすぐ反映</span>
        </div>
    </div>
</header>

<ol class="cp-steps" aria-label="クーポン作成の流れ">
    <li><a href="#coupon-basic"><span>01</span><strong>基本情報</strong><small>名前と画像</small></a></li>
    <li><a href="#coupon-benefit"><span>02</span><strong>特典</strong><small>割引内容</small></a></li>
    <li><a href="#coupon-delivery"><span>03</span><strong>渡し方</strong><small>発行タイミング</small></a></li>
    <li><a href="#coupon-conditions"><span>04</span><strong>利用条件</strong><small>期間と上限</small></a></li>
</ol>
@if(session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error" role="alert">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form action="{{ admin_route('admin.coupons.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($returnTo === 'bulk-issue')
        <input type="hidden" name="return" value="bulk-issue">
    @endif
    @include('admin.coupons._form', [
        'coupon' => null,
        'stampCards' => $stampCards ?? [],
        'submitLabel' => 'クーポンを作成',
        'cancelUrl' => $returnTo === 'bulk-issue' ? admin_route('admin.coupons.bulk-issue') : admin_route('admin.coupons'),
    ])
</form>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ config('app.asset_version') }}">
@endpush
@endsection
