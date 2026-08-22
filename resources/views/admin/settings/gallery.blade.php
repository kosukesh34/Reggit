@extends('admin.layout')
@section('title', 'ギャラリー設定')
@section('page-title', 'ギャラリー設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.gallery.categories') }}">ギャラリー</a><span class="breadcrumb-sep">/</span><span>ギャラリー設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-images"></i> ギャラリー設定</h1>
</div>
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
<form action="{{ admin_route('admin.settings.gallery.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">ギャラリー機能</label>
                <label class="checkbox-label">
                    <input type="hidden" name="feature_gallery_enabled" value="0">
                    <input type="checkbox" name="feature_gallery_enabled" value="1" {{ $featureEnabled ? 'checked' : '' }}>
                    <span>ギャラリー機能を有効にする</span>
                </label>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.gallery.categories') }}" class="btn btn-secondary">ギャラリーへ</a></div>
        </div>
    </div>
</form>
@endsection
