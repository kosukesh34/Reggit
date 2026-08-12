{{-- 管理画面共通: 画像ドラッグ&ドロップ + プレビュー --}}
@php
    $logoInputName = $logoInputName ?? 'frontend_logo';
    $logoClearName = $logoClearName ?? 'frontend_logo_clear';
    $logoCurrentUrl = (string) ($logoCurrentUrl ?? '');
    $logoFieldId = $logoFieldId ?? 'frontend_logo';
    $logoAccept = $logoAccept ?? 'image/*';
    $logoHint = $logoHint ?? 'PNG / JPG 2MBまで';
    $logoLabel = $logoLabel ?? '画像';
    $logoHelp = $logoHelp ?? '';
@endphp
<div class="form-group admin-image-upload" data-image-upload="{{ $logoFieldId }}">
    <label class="form-label">{{ $logoLabel }}</label>
    <input type="hidden" name="{{ $logoClearName }}" id="{{ $logoFieldId }}_clear_flag" value="0">
    <input type="file" name="{{ $logoInputName }}" id="{{ $logoFieldId }}" class="d-none" accept="{{ $logoAccept }}">

    <div class="coupon-image-preview admin-image-upload__preview" id="{{ $logoFieldId }}_preview" @if($logoCurrentUrl === '') hidden @endif>
        <p class="admin-image-upload__preview-label">現在の画像 / 選択中のプレビュー</p>
        <img id="{{ $logoFieldId }}_preview_img" src="{{ $logoCurrentUrl }}" alt="{{ $logoLabel }}のプレビュー">
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="{{ $logoFieldId }}_clear">画像をクリア</button>
    </div>

    <div class="coupon-image-dropzone" id="{{ $logoFieldId }}_dropzone" tabindex="0" role="button" aria-label="{{ $logoLabel }}を選択">
        <span class="coupon-image-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
        <span class="coupon-image-dropzone-text">クリックまたはドラッグ＆ドロップで画像を選択</span>
        <span class="coupon-image-dropzone-hint">{{ $logoHint }}</span>
    </div>

    @if($logoHelp !== '')
        <p class="form-text text-muted">{{ $logoHelp }}</p>
    @endif
    @error($logoInputName)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>

@once
@push('styles')
<style>
.admin-image-upload__preview {
    margin-bottom: 12px;
    padding: 12px;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    background: #fff;
}
.admin-image-upload__preview-label {
    margin: 0 0 8px;
    font-size: 12px;
    color: var(--text-muted, #64748b);
}
.admin-image-upload__preview img {
    display: block;
    max-width: 240px;
    max-height: 160px;
    width: auto;
    height: auto;
    object-fit: contain;
    background: repeating-conic-gradient(#f1f5f9 0% 25%, #fff 0% 50%) 50% / 16px 16px;
    border-radius: 4px;
}
.coupon-image-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 2px dashed var(--border-color, #cbd5e1);
    border-radius: 8px;
    background: var(--bg-secondary, #f8fafc);
    cursor: pointer;
    text-align: center;
}
.coupon-image-dropzone:hover,
.coupon-image-dropzone.is-dragover {
    border-color: var(--primary, #0d9488);
    background: rgba(13, 148, 136, 0.05);
}
.coupon-image-dropzone-icon { font-size: 1.75rem; color: var(--primary, #0d9488); margin-bottom: 6px; }
.coupon-image-dropzone-text { font-size: 13px; color: var(--text-primary); }
.coupon-image-dropzone-hint { font-size: 11px; color: var(--text-muted, #94a3b8); margin-top: 2px; }
</style>
@endpush
@endonce

@push('scripts')
<script>
(function () {
    var fieldId = @json($logoFieldId);
    var fileInput = document.getElementById(fieldId);
    var dropzone = document.getElementById(fieldId + '_dropzone');
    var preview = document.getElementById(fieldId + '_preview');
    var previewImg = document.getElementById(fieldId + '_preview_img');
    var clearBtn = document.getElementById(fieldId + '_clear');
    var clearFlag = document.getElementById(fieldId + '_clear_flag');
    if (!fileInput || !dropzone) return;

    function showPreview(src) {
        if (preview === null || previewImg === null) return;
        previewImg.src = src || '';
        if (src) {
            preview.hidden = false;
        } else {
            preview.hidden = true;
        }
    }

    function readFile(file) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
        if (clearFlag) clearFlag.value = '0';
        var reader = new FileReader();
        reader.onload = function (e) { showPreview(e.target.result); };
        reader.readAsDataURL(file);
    }

    dropzone.addEventListener('click', function () { fileInput.click(); });
    dropzone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', function () {
        dropzone.classList.remove('is-dragover');
    });
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (f) {
            fileInput.files = e.dataTransfer.files;
            readFile(f);
        }
    });
    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) readFile(fileInput.files[0]);
    });
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            fileInput.value = '';
            if (clearFlag) clearFlag.value = '1';
            showPreview('');
        });
    }
})();
</script>
@endpush
