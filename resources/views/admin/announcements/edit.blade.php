@extends('admin.layout')

@section('title', 'お知らせ 編集')
@section('page-title', 'お知らせ 編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-edit"></i> お知らせ 編集</h1>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ admin_route('admin.announcements.update', ['id' => $announcement->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">内容</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $announcement->title) }}" required maxlength="100">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">本文 <span class="text-danger">*</span></label>
                <textarea name="body" id="body" class="form-control" rows="5" required maxlength="2000">{{ old('body', $announcement->body) }}</textarea>
            </div>
            @include('admin.announcements._images', ['currentImages' => $announcement->imageUrls()])
            <div class="form-group">
                <label for="image_url" class="form-label">または 画像URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url', $announcement->image_url) }}" maxlength="500" placeholder="https://example.com/image.png">
            </div>
            <div class="form-group">
                <label for="published_at" class="form-label">公開日</label>
                <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="{{ old('published_at', $announcement->published_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-group">
                <label for="expires_at" class="form-label">掲載終了</label>
                <input type="datetime-local" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="hidden" name="is_pinned" value="0">
                    <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }}>
                    固定表示
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">更新</button>
        <x-back-link :href="admin_route('admin.announcements')" label="キャンセル" />
    </div>
</form>
@endsection
