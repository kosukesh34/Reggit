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
        <x-back-link :href="route('system-admin.projects.index')" label="キャンセル" />
    </div>
</form>
<form class="card mt-3" action="{{ route('system-admin.projects.app-links.update', $view->project->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card-header"><h2 class="card-title">ネイティブアプリのダウンロードURL</h2></div>
    <div class="card-body">
        <p class="text-muted">店舗の「QRコード発行」画面に表示するURLです。空欄にすると、そのQRは非表示になります。</p>
        @foreach(['download_url' => '共通ダウンロード案内URL'] as $field => $label)
        <div class="form-group">
            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
            <input class="form-control" type="url" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $appLinks?->{$field}) }}" maxlength="2048" placeholder="https://">
            @error($field)<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        @endforeach
        <button class="btn btn-primary" type="submit">ダウンロードURLを保存</button>
    </div>
</form>
@endsection
