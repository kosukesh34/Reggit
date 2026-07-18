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
            <div class="form-group">
                <span class="form-label d-block">画像</span>
                <p class="text-muted small mb-2">ドラッグ＆ドロップ、ファイル選択、またはURLで画像を追加できます。</p>
                <div class="coupon-image-upload-wrap mb-2">
                    <input type="file" name="image_file" id="ann_image" class="d-none" accept="image/*">
                    <label for="ann_image" class="coupon-image-dropzone" id="ann_image_dropzone">
                        <span class="coupon-image-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                        <span class="coupon-image-dropzone-text">クリックまたはドラッグ＆ドロップ</span>
                        <span class="coupon-image-dropzone-hint">PNG / JPG 2MBまで</span>
                    </label>
                    <div class="coupon-image-preview mt-2" id="ann_image_preview" style="{{ old('image_url', $announcement->image_url) ? '' : 'display:none;' }}">
                        <img id="ann_image_preview_img" src="{{ old('image_url', $announcement->image_url) }}" alt="" style="max-width:300px;max-height:150px;object-fit:contain;">
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="ann_image_clear">画像を解除</button>
                    </div>
                </div>
                <label for="image_url" class="form-label small">または 画像URL</label>
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
        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">キャンセル</a>
    </div>
</form>
<script>
(function() {
    var fileInput = document.getElementById('ann_image');
    var dropzone = document.getElementById('ann_image_dropzone');
    var preview = document.getElementById('ann_image_preview');
    var previewImg = document.getElementById('ann_image_preview_img');
    var clearBtn = document.getElementById('ann_image_clear');
    var urlInput = document.getElementById('image_url');
    if (!fileInput || !dropzone) return;
    function showPreview(src) {
        if (preview && previewImg) { previewImg.src = src || ''; preview.style.display = src ? 'block' : 'none'; }
    }
    function readFile(file) {
        if (!file || !file.type.match(/^image\//)) return;
        var r = new FileReader();
        r.onload = function() { showPreview(r.result); };
        r.readAsDataURL(file);
    }
    dropzone.addEventListener('click', function(e) { if (e.target === dropzone || dropzone.contains(e.target)) fileInput.click(); });
    dropzone.addEventListener('dragover', function(e) { e.preventDefault(); dropzone.classList.add('is-dragover'); });
    dropzone.addEventListener('dragleave', function() { dropzone.classList.remove('is-dragover'); });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault(); dropzone.classList.remove('is-dragover');
        var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (f) { fileInput.files = e.dataTransfer.files; readFile(f); }
    });
    fileInput.addEventListener('change', function() { var f = this.files && this.files[0]; if (f) readFile(f); else showPreview(urlInput ? urlInput.value : null); });
    if (clearBtn) clearBtn.addEventListener('click', function() {
        fileInput.value = '';
        if (urlInput) urlInput.value = '';
        showPreview(null);
    });
    if (urlInput) urlInput.addEventListener('input', function() {
        if (!fileInput.files || !fileInput.files.length) showPreview(urlInput.value || null);
    });
})();
</script>
@push('styles')
<style>
.coupon-image-dropzone {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 20px; border: 2px dashed var(--border, #dee2e6); border-radius: 12px;
    background: var(--bg-secondary, #f8f9fa); cursor: pointer; transition: border-color .2s, background .2s;
}
.coupon-image-dropzone:hover, .coupon-image-dropzone.is-dragover { border-color: var(--primary, #0d9488); background: rgba(13, 148, 136, 0.05); }
.coupon-image-dropzone-icon { font-size: 1.8rem; color: var(--text-secondary); margin-bottom: 6px; }
.coupon-image-dropzone-text { font-size: 13px; color: var(--text-primary); }
.coupon-image-dropzone-hint { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
</style>
@endpush
@endsection
