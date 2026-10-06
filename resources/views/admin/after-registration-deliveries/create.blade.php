@extends('admin.layout')

@section('title', 'スケジュール配信を追加')
@section('page-title', 'スケジュール配信を追加')
@section('page-kind', 'scheduled-delivery-form')

@section('page-header-actions')
    <button type="submit" form="scheduledDeliveryForm" class="btn btn-primary ad-header-submit">
        <i class="fas fa-plus" aria-hidden="true"></i> 追加する
    </button>
@endsection

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.after-registration-deliveries.index') }}">スケジュール配信</a>
    <span class="breadcrumb-sep">/</span>
    <span>追加</span>
</nav>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>入力内容を確認してください</strong>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ admin_route('admin.after-registration-deliveries.store') }}" method="POST" id="scheduledDeliveryForm">
    @csrf
    @include('admin.after-registration-deliveries._form')
</form>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/announcement-delivery.css') }}?v={{ (int) @filemtime(public_path('css/admin/announcement-delivery.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin/scheduled-deliveries.css') }}?v={{ (int) @filemtime(public_path('css/admin/scheduled-deliveries.css')) }}">
@endpush
@endsection
