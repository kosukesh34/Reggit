@extends('admin.layout')

@section('title', 'プロジェクト入力ホーム')
@section('page-title', 'プロジェクト入力ホーム')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.projects.index') }}">プロジェクト管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>入力ホーム</span>
</nav>

<form action="{{ route('system-admin.projects.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.projects._form')
    <div class="form-actions mt-3">
        <button type="submit" class="btn btn-primary">プロジェクトを作成</button>
        <x-back-link :href="route('system-admin.projects.index')" label="キャンセル" />
    </div>
</form>
@endsection
