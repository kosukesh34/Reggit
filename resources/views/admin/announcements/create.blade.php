@extends('admin.layout')

@section('title', 'お知らせ 追加')
@section('page-title', 'お知らせ 追加')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>追加</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-bullhorn"></i> お知らせ 追加</h1>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ admin_route('admin.announcements.store') }}" method="POST">
    @csrf
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">内容</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required maxlength="100">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">本文 <span class="text-danger">*</span></label>
                <textarea name="body" id="body" class="form-control" rows="5" required maxlength="2000">{{ old('body') }}</textarea>
            </div>
            <div class="form-group">
                <label for="published_at" class="form-label">公開日</label>
                <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="{{ old('published_at') }}">
            </div>
            <div class="form-group">
                <label for="expires_at" class="form-label">掲載終了</label>
                <input type="datetime-local" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at') }}">
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    有効
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">登録</button>
        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">キャンセル</a>
    </div>
</form>
@endsection
