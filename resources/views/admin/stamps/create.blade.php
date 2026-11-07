@extends('admin.layout')
@section('title', 'スタンプカード追加')
@section('page-title', 'スタンプカード追加')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>

<div class="page-header"><h1 class="page-header-title">スタンプカード追加</h1><p class="page-header-desc">入力内容は右側のプレビューへ即時反映されます。達成特典は複数設定できます。</p></div>

@include('admin.stamps._form', [
    'stampCard' => null,
    'formAction' => admin_route('admin.stamps.store'),
    'submitLabel' => '追加',
])
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/stamps-studio.css') }}?v={{ (int) @filemtime(public_path('css/admin/stamps-studio.css')) }}">
@endpush
