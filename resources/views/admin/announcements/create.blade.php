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

<div class="card card-mb-16">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-magic"></i> クイックテンプレート</h3>
        <span class="card-header-note">クリックで入力欄に反映</span>
    </div>
    <div class="card-body">
        <div class="announcement-templates">
            @foreach([
                ['label' => '🎉 キャンペーン', 'title' => '期間限定キャンペーン開催中！', 'body' => "いつもご利用いただきありがとうございます。\n期間限定キャンペーンを開催中です！\n詳しくはスタッフにお問い合わせください。"],
                ['label' => '🏪 新商品', 'title' => '新商品のご案内', 'body' => "新商品が登場しました！\nぜひ一度お試しください。"],
                ['label' => '🎁 ポイント', 'title' => 'ポイントアップデー開催', 'body' => "期間中のご利用でポイントが通常の2倍になります！\nこの機会にぜひご来店ください。"],
                ['label' => '⏰ お休み', 'title' => '臨時休業のお知らせ', 'body' => "誠に勝手ながら、下記の日程で臨時休業いたします。\nご迷惑をおかけいたしますが、よろしくお願いいたします。"],
            ] as $tmpl)
            <button type="button" class="btn btn-sm btn-secondary announcement-template-btn"
                    data-title="{{ $tmpl['title'] }}" data-body="{{ $tmpl['body'] }}">
                {{ $tmpl['label'] }}
            </button>
            @endforeach
        </div>
    </div>
</div>

<form action="{{ admin_route('admin.announcements.store') }}" method="POST" enctype="multipart/form-data">
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
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'image_file',
                'logoClearName' => 'image_file_clear',
                'logoFieldId' => 'ann_image',
                'logoCurrentUrl' => (string) old('image_url', ''),
                'logoLabel' => '画像',
            ])
            <div class="form-group">
                <label for="image_url" class="form-label">または 画像URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/image.png">
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
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                    固定表示
                </label>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">登録</button>
        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">キャンセル</a>
    </div>
</form>
<script>
(function() {
    document.querySelectorAll('.announcement-template-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var t = document.getElementById('title');
            var b = document.getElementById('body');
            if (t) t.value = btn.getAttribute('data-title') || '';
            if (b) b.value = btn.getAttribute('data-body') || '';
        });
    });
})();
</script>
@push('styles')
<style>
.announcement-templates { display: flex; flex-wrap: wrap; gap: 8px; }
</style>
@endpush
@endsection
