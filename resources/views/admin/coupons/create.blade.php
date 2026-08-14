@extends('admin.layout')
@section('title', 'クーポン作成')
@section('page-title', 'クーポン作成')
@section('page-kind', 'coupon-create')
@section('content')
<div class="coupon-admin">
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規</span>
</nav>
<ol class="cp-steps" aria-label="クーポン作成の流れ">
    <li><a href="#coupon-basic"><span>01</span><strong>基本情報</strong><small>名前と画像</small></a></li>
    <li><a href="#coupon-benefit"><span>02</span><strong>特典</strong><small>割引内容</small></a></li>
    <li><a href="#coupon-conditions"><span>03</span><strong>発行条件</strong><small>期間と適用</small></a></li>
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
    @include('admin.coupons._form', [
        'coupon' => null,
        'submitLabel' => 'クーポンを作成',
        'cancelUrl' => admin_route('admin.coupons'),
    ])
</form>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ (int) @filemtime(public_path('css/admin/coupons.css')) }}">
@endpush
@endsection
