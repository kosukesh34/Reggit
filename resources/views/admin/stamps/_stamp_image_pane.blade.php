@php
    $kind = $kind ?? 'filled';
    $isEmpty = $kind === 'empty';
    $title = $isEmpty ? '押す前' : '押したあと';
    $idPrefix = $isEmpty ? 'stampEmptyImage' : 'stampImage';
@endphp
<div class="si-editor-pane" data-stamp-image-kind="{{ $kind }}">
    <p class="si-editor-pane-title">{{ $title }}</p>
    <div class="si-dropzone si-dropzone-sm" id="{{ $idPrefix }}Dropzone" tabindex="0">
        <input type="file" class="si-file-input" id="{{ $idPrefix }}FileInput" accept="image/png,image/jpeg,image/gif,image/webp">
        <div class="si-dropzone-content" id="{{ $idPrefix }}DropzoneContent">
            <span class="si-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
            <span class="si-dropzone-text">ドラッグ＆ドロップ</span>
        </div>
        <div class="si-dropzone-preview" id="{{ $idPrefix }}Preview" hidden>
            <img id="{{ $idPrefix }}PreviewImg" src="" alt="">
            <button type="button" class="si-dropzone-clear" id="{{ $idPrefix }}Clear"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="si-editor-url">
        <label class="form-label small" for="{{ $idPrefix }}UrlInput">または URL</label>
        <div class="si-editor-url-row">
            <input type="url" class="form-control form-control-sm" id="{{ $idPrefix }}UrlInput" placeholder="https://example.com/stamp.png">
            <button type="button" class="btn btn-sm btn-primary" id="{{ $idPrefix }}UrlApply">適用</button>
        </div>
    </div>
</div>
