@extends('admin.layout')
@section('title', 'スタンプカード編集')
@section('page-title', 'スタンプカード編集')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>編集</span></nav>

@include('admin.stamps._form', [
    'stampCard' => $view->stampCard,
    'formAction' => admin_route('admin.stamps.update', ['id' => $view->stampCard->id]),
    'submitLabel' => '更新',
])
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/stamps-studio.css') }}?v={{ (int) @filemtime(public_path('css/admin/stamps-studio.css')) }}">
@endpush
