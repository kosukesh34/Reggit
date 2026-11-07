@extends('admin.layout')

@section('title', 'プロジェクト管理者アカウント作成')
@section('page-title', 'プロジェクト管理者アカウント作成')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route($view->routePrefix.'.accounts.index', $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}">アカウント管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規作成</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> 新規プロジェクト管理者アカウント</h3>
    </div>
    <div class="card-body">
        <form action="{{ route("{$view->routePrefix}.accounts.store", $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}" method="POST">
            @csrf
            @include('admin.accounts._form', [
                'adminUser' => null,
                'submitLabel' => '作成',
            ])
        </form>
    </div>
</div>

@endsection
