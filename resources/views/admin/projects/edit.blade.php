@extends('admin.layout')

@section('title', '契約・連携設定')
@section('page-title', '契約・連携設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.projects.index') }}">プロジェクト管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>契約・連携設定</span>
</nav>

<form action="{{ route('system-admin.projects.update', $view->project->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.projects._form')
    <div class="form-actions mt-3">
        <button type="submit" class="btn btn-primary">設定を更新</button>
        <a href="{{ route('system-admin.projects.index') }}" class="btn btn-secondary">キャンセル</a>
    </div>
</form>
@endsection
