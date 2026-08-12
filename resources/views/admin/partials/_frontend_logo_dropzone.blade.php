{{-- ヘッダーロゴのドラッグ&ドロップ（機能設定・プロジェクト入力ホーム共通） --}}
@php
    $logoInputName = $logoInputName ?? 'frontend_logo';
    $logoClearName = $logoClearName ?? 'frontend_logo_clear';
    $logoCurrentUrl = (string) ($logoCurrentUrl ?? '');
    $logoFieldId = $logoFieldId ?? 'frontend_logo';
@endphp
<div class="form-group">
    <label class="form-label">{{ $logoLabel ?? 'ヘッダー画像（ロゴ）' }}</label>
    <input type="hidden" name="{{ $logoClearName }}" id="{{ $logoFieldId }}_clear_flag" value="0">
    <input type="file" name="{{ $logoInputName }}" id="{{ $logoFieldId }}" class="d-none" accept="image/*">
    <div class="coupon-image-dropzone" id="{{ $logoFieldId }}_dropzone" tabindex="0" role="button" aria-label="ロゴ画像を選択">
        <span class="coupon-image-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
        <span class="coupon-image-dropzone-text">クリックまたはドラッグ＆ドロップで画像を選択</span>
        <span class="coupon-image-dropzone-hint">PNG / JPG 2MBまで</span>
    </div>
    <div class="coupon-image-preview mt-2" id="{{ $logoFieldId }}_preview" style="{{ $logoCurrentUrl !== '' ? '' : 'display:none;' }}">
        <img id="{{ $logoFieldId }}_preview_img" src="{{ $logoCurrentUrl }}" alt="" style="max-width:200px;max-height:120px;object-fit:contain;">
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="{{ $logoFieldId }}_clear">画像をクリア</button>
    </div>
    @if(($logoHelp ?? '') !== '')
        <p class="form-text text-muted">{{ $logoHelp }}</p>
    @endif
    @error($logoInputName)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>

@once
@push('styles')
<style>
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
        if (preview && previewImg) {
            previewImg.src = src || '';
            preview.style.display = src ? '' : 'none';
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
