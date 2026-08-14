@extends('admin.layout')

@section('title', 'スケジュール配信を編集')
@section('page-title', 'スケジュール配信を編集')
@section('page-kind', 'scheduled-delivery-form')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.after-registration-deliveries.index') }}">スケジュール配信</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-edit"></i> スケジュール配信を編集</h1>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ admin_route('admin.after-registration-deliveries.update', ['id' => $view->delivery->id]) }}" method="POST" id="scheduledDeliveryForm">
    @csrf
    @method('PUT')
    @include('admin.after-registration-deliveries._form')
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 更新する</button>
        <a href="{{ admin_route('admin.after-registration-deliveries.index') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
</form>
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/scheduled-deliveries.css') }}?v={{ (int) @filemtime(public_path('css/admin/scheduled-deliveries.css')) }}">
@endpush
@endsection
