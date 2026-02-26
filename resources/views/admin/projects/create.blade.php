@extends('admin.layout')

@section('title', 'プロジェクト入力ホーム')
@section('page-title', 'プロジェクト入力ホーム')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.projects.index') }}">プロジェクト管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>入力ホーム</span>
</nav>

<div class="page-header system-admin-page-header">
    <p class="page-header-desc">プロジェクトの基本情報・LINE連携・初期表示をこの画面で一括設定できます。</p>
</div>

<form action="{{ route('system-admin.projects.store') }}" method="POST">
    @csrf
    @include('admin.projects._form')
    <div class="form-actions mt-3">
        <button type="submit" class="btn btn-primary">プロジェクトを作成</button>
        <a href="{{ route('system-admin.projects.index') }}" class="btn btn-secondary">キャンセル</a>
    </div>
</form>
@endsection
