@extends('admin.layout')
@section('title', 'お知らせの設定')
@section('page-title', 'お知らせの設定')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a><span class="breadcrumb-sep">/</span><a href="{{ admin_route('admin.announcements') }}">お知らせ</a><span class="breadcrumb-sep">/</span><span>お知らせの設定</span></nav>
<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせの設定</h1>
</div>
@if(session('success'))<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
<form action="{{ admin_route('admin.settings.announcements.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">お知らせ機能</label>
                <label class="checkbox-label">
                    <input type="hidden" name="feature_announcements_enabled" value="0">
                    <input type="checkbox" name="feature_announcements_enabled" value="1" {{ $view->featureEnabled ? 'checked' : '' }}>
                    <span>お知らせ機能を有効にする</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">新規お知らせのデフォルト</label>
                <label class="checkbox-label">
                    <input type="hidden" name="announcement_default_pinned" value="0">
                    <input type="checkbox" name="announcement_default_pinned" value="1" {{ $view->defaultPinned ? 'checked' : '' }}>
                    <span>固定表示にする</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">デフォルト掲載期間（日数）</label>
                <input type="number" name="announcement_default_expiry_days" class="form-control" value="{{ old('announcement_default_expiry_days', $view->defaultExpiryDays) }}" min="0" max="365">
                <small class="form-text text-muted">0 の場合は掲載終了日を設定しません。</small>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button> <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">お知らせ一覧へ</a></div>
        </div>
    </div>
</form>
@endsection
