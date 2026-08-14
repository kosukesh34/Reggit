{{-- 管理画面共通: 画像ドラッグ&ドロップ + プレビュー --}}
@php
    $logoInputName = $logoInputName ?? 'frontend_logo';
    $logoClearName = $logoClearName ?? 'frontend_logo_clear';
    $logoCurrentUrl = (string) ($logoCurrentUrl ?? '');
    $logoFieldId = $logoFieldId ?? 'frontend_logo';
    $logoAccept = $logoAccept ?? 'image/png,image/jpeg,image/gif,image/webp';
    $logoHint = $logoHint ?? 'PNG / JPG 2MBまで';
    $logoLabel = $logoLabel ?? '画像';
    $logoHelp = $logoHelp ?? '';
    $logoCompact = $logoCompact ?? false;
    $logoRequired = $logoRequired ?? false;
@endphp
<div class="form-group admin-image-upload{{ $logoCompact ? ' admin-image-upload--compact' : '' }}" data-image-upload="{{ $logoFieldId }}">
    <label class="form-label" for="{{ $logoFieldId }}">{{ $logoLabel }}</label>
    <input type="hidden" name="{{ $logoClearName }}" id="{{ $logoFieldId }}_clear_flag" value="0">
    <input
        type="file"
        name="{{ $logoInputName }}"
        id="{{ $logoFieldId }}"
        class="admin-image-upload__file"
        accept="{{ $logoAccept }}"
        @if($logoRequired && $logoCurrentUrl === '') required @endif
    >

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
