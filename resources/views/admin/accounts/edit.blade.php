@extends('admin.layout')

@section('title', 'プロジェクト管理者アカウント編集')
@section('page-title', 'プロジェクト管理者アカウント編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route($view->routePrefix.'.accounts.index', $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}">アカウント管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> プロジェクト管理者アカウント編集</h3>
    </div>
    <div class="card-body">
        <form action="{{ route("{$view->routePrefix}.accounts.update", array_merge(
            ['id' => $view->adminUser->id],
            $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []
        )) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.accounts._form', [
                'adminUser' => $view->adminUser,
                'submitLabel' => '更新',
            ])
        </form>
    </div>
</div>

@endsection
