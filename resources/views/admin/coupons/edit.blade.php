@extends('admin.layout')
@section('title', 'クーポン編集')
@section('page-title', 'クーポン編集')
@section('page-kind', 'coupon-edit')
@section('content')
<div class="coupon-admin">
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.coupons') }}">クーポン</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>
<div class="page-header">
    <p class="page-header-desc">特典内容と利用条件を更新します。すでに配布したクーポンには影響しません。</p>
</div>
@if(session('error'))
<div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error" role="alert">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form action="{{ admin_route('admin.coupons.update', ['id' => $coupon->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.coupons._form', [
        'coupon' => $coupon,
        'submitLabel' => '変更を保存',
        'cancelUrl' => admin_route('admin.coupons'),
    ])
</form>
<div class="cp-delete-row">
    <button type="button" class="btn btn-danger" onclick="if(confirm('このクーポンを削除しますか？\n既に会員に発行済みのクーポンは残りますが、マスタからは削除されます。')) document.getElementById('coupon-delete-form').submit();">削除</button>
</div>
<form id="coupon-delete-form" action="{{ admin_route('admin.coupons.delete', ['id' => $coupon->id]) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
</div>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}?v={{ (int) @filemtime(public_path('css/admin/coupons.css')) }}">
@endpush
@endsection
