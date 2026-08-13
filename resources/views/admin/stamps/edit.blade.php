@extends('admin.layout')
@section('title', 'スタンプカード編集')
@section('page-title', 'スタンプカード編集')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>編集</span></nav>
@php($layoutOptions = \App\Models\StampCard::getLayoutOptions())
@php($coupons = $coupons ?? collect())
@php($milestones = old('reward_milestones', $stampCard->reward_milestones ?? [['stamp_count' => '']]))
@if(!is_array($milestones) || $milestones === [])
    @php($milestones = [['stamp_count' => '']])
@else
    @php($milestones = array_values(array_filter($milestones, static function ($milestone): bool {
        if (! is_array($milestone)) {
            return false;
        }
        $rewardType = (string) ($milestone['reward_type'] ?? '');

        return $rewardType === '' || in_array($rewardType, \App\Enums\StampRewardType::keys(), true);
    })))
    @if($milestones === [])
        @php($milestones = [['stamp_count' => '']])
    @endif
@endif
@php($existingStampImages = old('stamp_images_data') ? json_decode(old('stamp_images_data'), true) : ($stampCard->stamp_images ?? []))
@if(!is_array($existingStampImages)) @php($existingStampImages = []) @endif
@php($existingStampEmptyImages = old('stamp_empty_images_data') ? json_decode(old('stamp_empty_images_data'), true) : ($stampCard->stamp_empty_images ?? []))
@if(!is_array($existingStampEmptyImages)) @php($existingStampEmptyImages = []) @endif

<div class="stamp-studio">
    <section class="card stamp-studio-form-card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp"></i> スタンプカード編集</h3></div>
        <div class="card-body">
            <form action="{{ admin_route('admin.stamps.update', ['id' => $stampCard->id]) }}" method="POST" data-stamp-edit-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="valid_days" value="">

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head"><h4>基本情報</h4></div>
                    <div class="stamp-form-grid">
                        <div class="form-group stamp-form-col-span-2">
                            <label for="name" class="form-label">カード名 <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $stampCard->name) }}" required maxlength="255">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="required_stamps" class="form-label">必要スタンプ数 <span class="text-danger">*</span></label>
                            <input type="number" name="required_stamps" id="required_stamps" class="form-control" value="{{ old('required_stamps', $stampCard->required_stamps) }}" min="1" max="999" required>
                            @error('required_stamps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="layout_style" class="form-label">レイアウト</label>
                            <select name="layout_style" id="layout_style" class="form-control">
                                @foreach($layoutOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('layout_style', $stampCard->layout_style ?? 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head"><h4>有効期間・公開</h4></div>
                    <div class="stamp-form-grid">
                        <div class="form-group">
                            <label for="valid_until" class="form-label">有効期限（日付）</label>
                            <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until', $stampCard->valid_until?->format('Y-m-d')) }}">
                            <p class="form-text text-muted">未設定の場合は無期限</p>
                            @error('valid_until')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group form-check stamp-form-check-wrap">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', $stampCard->is_active)) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">有効（公開）</label>
                        </div>
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head stamp-form-section-head-with-action">
                        <h4>達成特典（複数設定可）</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="editAddMilestoneButton"><i class="fas fa-plus"></i> 特典を追加</button>
                    </div>
                    <div class="stamp-milestones" id="editMilestonesContainer">
                        @foreach($milestones as $index => $milestone)
                            @include('admin.stamps._milestone_row', [
                                'index' => $index,
                                'milestone' => is_array($milestone) ? $milestone : [],
                                'coupons' => $coupons,
                                'isNamed' => true,
                                'idPrefix' => 'edit_reward_milestones',
                            ])
                        @endforeach
                    </div>
                    <p class="form-text text-muted">到達時にクーポンまたはガチャ権利が自動で付与されます。</p>
                    @if(($isGachaEnabled ?? false) !== true)
                    <p class="form-text text-muted">ガチャ特典を使う場合は、設定のガチャを有効にしてください。</p>
                    @endif
                    @error('reward_milestones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.stamp_count')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.reward_type')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror

                    <div class="form-group stamp-form-note-group">
                        <label for="reward_description" class="form-label">補足テキスト</label>
                        <input type="text" name="reward_description" id="reward_description" class="form-control" value="{{ old('reward_description', $stampCard->reward_description) }}" maxlength="500">
                        @error('reward_description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head"><h4>ビジュアル</h4></div>

                    <div class="stamp-visual-subsection">
                        <label class="form-label">カード画像</label>
                        <p class="form-text text-muted">ドラッグ＆ドロップまたはURLで画像を設定できます。</p>
                        <div class="si-dropzone" id="cardImageDropzone" tabindex="0">
                            <input type="file" class="si-file-input" id="cardImageFileInput" accept="image/png,image/jpeg,image/gif,image/webp">
                            <div class="si-dropzone-content" id="cardImageDropzoneContent" @if($stampCard->image_url) hidden @endif>
                                <span class="si-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                <span class="si-dropzone-text">クリックまたはドラッグ＆ドロップ</span>
                                <span class="si-dropzone-hint">PNG / JPG 2MBまで</span>
                            </div>
                            <div class="si-dropzone-preview" id="cardImagePreview" @if(!$stampCard->image_url) hidden @endif>
                                <img id="cardImagePreviewImg" src="{{ $stampCard->image_url ?? '' }}" alt="">
                                <button type="button" class="si-dropzone-clear" id="cardImageClear"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div class="si-url-row">
                            <label for="image_url" class="form-label small">または URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url', $stampCard->image_url) }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                        </div>
                        <input type="hidden" name="image_data" id="imageData">
                        @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="stamp-visual-subsection stamp-images-section">
                        <label class="form-label">個別スタンプ画像</label>
                        <p class="form-text text-muted">各スタンプ位置をクリックして、押す前と押したあとの画像を設定できます。未設定の場合は番号またはデフォルトアイコンが表示されます。</p>

                        <div class="si-grid" id="stampImagesGrid"></div>

                        <div class="si-editor" id="stampImageEditor" hidden>
                            <div class="si-editor-header">
                                <span><i class="fas fa-image"></i> スタンプ <strong id="stampImageEditorPos">1</strong></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary si-editor-close" id="stampImageEditorClose"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="si-editor-body">
                                @include('admin.stamps._stamp_image_pane', ['kind' => 'empty'])
                                @include('admin.stamps._stamp_image_pane', ['kind' => 'filled'])
                            </div>
                        </div>

                        <div class="si-bulk-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="stampImageBulkApply" disabled>
                                <i class="fas fa-copy"></i> 選択中の画像を全スタンプに適用
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="stampImageBulkClear">
                                <i class="fas fa-eraser"></i> 全クリア
                            </button>
                        </div>

                        <input type="hidden" name="stamp_images_data" id="stampImagesData">
                        <input type="hidden" name="stamp_empty_images_data" id="stampEmptyImagesData">
                    </div>
                </div>

                <div class="form-actions stamp-form-actions">
                    <button type="submit" class="btn btn-primary">更新</button>
                    <a href="{{ admin_route('admin.stamps') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </form>

            <form action="{{ admin_route('admin.stamps.delete', ['id' => $stampCard->id]) }}" method="POST" class="mt-3" onsubmit="return confirm('このスタンプカードを削除しますか？');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">削除</button>
            </form>
        </div>
    </section>

    <aside class="card stamp-studio-preview-card">
        <div class="card-header stamp-preview-card-header">
            <h3 class="card-title"><i class="fas fa-wand-magic-sparkles"></i> ライブプレビュー</h3>
        </div>
        <div class="card-body">
            <p class="stamp-preview-sub">会員アプリの表示イメージ（近似）</p>
            <div class="stamp-preview-device">
                <div class="stamp-preview-notch" aria-hidden="true"></div>
                <div class="stamp-preview-screen">
                    <p class="stamp-preview-screen-title">スタンプカード</p>
                    <article class="stamp-preview-item" id="stampCardPreview" aria-live="polite">
                        <div class="stamp-preview-image-wrap">
                            <img src="" alt="スタンプカード画像" class="stamp-preview-image" id="stampPreviewImage">
                            <div class="stamp-preview-image-fallback" id="stampPreviewImageFallback"><i class="fas fa-stamp" aria-hidden="true"></i></div>
                        </div>
                        <header class="stamp-preview-header">
                            <h4 class="stamp-preview-name" id="stampPreviewName">スタンプカード名</h4>
                            <span class="stamp-preview-state" id="stampPreviewState">有効</span>
                        </header>
                        <p class="stamp-preview-layout" id="stampPreviewLayoutLabel">グリッド（2列）</p>
                        <div class="stamp-preview-slots" id="stampPreviewSlots"></div>
                        <p class="stamp-preview-overflow" id="stampPreviewOverflow" hidden></p>
                        <div class="stamp-preview-progress" id="stampPreviewProgress">
                            <div class="stamp-preview-progress-bar" id="stampPreviewProgressBar"></div>
                        </div>
                        <div class="stamp-preview-meta">
                            <p class="stamp-preview-count" id="stampPreviewCount">8 / 20 スタンプ</p>
                            <p class="stamp-preview-validity" id="stampPreviewValidity">有効期限: 無期限</p>
                        </div>
                        <section class="stamp-preview-milestones-wrap" id="stampPreviewMilestonesWrap">
                            <p class="stamp-preview-milestones-title">達成特典</p>
                            <ul class="stamp-preview-milestones" id="stampPreviewMilestones"></ul>
                        </section>
                        <p class="stamp-preview-reward-note" id="stampPreviewRewardNote">補足テキストを入力するとここに表示されます。</p>
                    </article>
                </div>
            </div>
        </div>
    </aside>
</div>

<template id="editMilestoneRowTemplate">
    @include('admin.stamps._milestone_row', [
        'index' => 0,
        'milestone' => [],
        'coupons' => $coupons,
        'isNamed' => false,
        'idPrefix' => 'edit_reward_milestones',
    ])
</template>

<script>
(() => {
    const form = document.querySelector('[data-stamp-edit-form]');
    if (!form) return;

    const container = document.getElementById('editMilestonesContainer');
    const addButton = document.getElementById('editAddMilestoneButton');
    const template = document.getElementById('editMilestoneRowTemplate');

    const fields = {
        name: form.querySelector('#name'),
        requiredStamps: form.querySelector('#required_stamps'),
        layoutStyle: form.querySelector('#layout_style'),
        validUntil: form.querySelector('#valid_until'),
        rewardDescription: form.querySelector('#reward_description'),
        imageUrl: form.querySelector('#image_url'),
        isActive: form.querySelector('#is_active'),
    };

    const preview = {
        card: document.getElementById('stampCardPreview'),
        image: document.getElementById('stampPreviewImage'),
        imageFallback: document.getElementById('stampPreviewImageFallback'),
        name: document.getElementById('stampPreviewName'),
        state: document.getElementById('stampPreviewState'),
        layoutLabel: document.getElementById('stampPreviewLayoutLabel'),
        slots: document.getElementById('stampPreviewSlots'),
        overflow: document.getElementById('stampPreviewOverflow'),
        progress: document.getElementById('stampPreviewProgress'),
        progressBar: document.getElementById('stampPreviewProgressBar'),
        count: document.getElementById('stampPreviewCount'),
        validity: document.getElementById('stampPreviewValidity'),
        milestonesWrap: document.getElementById('stampPreviewMilestonesWrap'),
        milestones: document.getElementById('stampPreviewMilestones'),
        rewardNote: document.getElementById('stampPreviewRewardNote'),
    };

    const maxPreviewSlots = { grid: 24, row: 24, circle: 25, minimal: 40, card: 24 };
    const toNumber = (v, fb) => { const n = parseInt(v, 10); return isNaN(n) ? fb : n; };
    const formatDate = (v) => {
        if (!v) return null;
        const d = new Date(v + 'T00:00:00');
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' });
    };
    const getLayoutLabel = () => {
        const sel = fields.layoutStyle.options[fields.layoutStyle.selectedIndex];
        return sel ? sel.textContent.trim() : 'グリッド（2列）';
    };
    const esc = (s) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    // ── Stamp Images State ──
    const existingStampImages = @json($existingStampImages);
    const existingStampEmptyImages = @json($existingStampEmptyImages);
    const stampImages = {};
    const stampEmptyImages = {};
    Object.keys(existingStampImages).forEach(k => { if (existingStampImages[k]) stampImages[k] = existingStampImages[k]; });
    Object.keys(existingStampEmptyImages).forEach(k => { if (existingStampEmptyImages[k]) stampEmptyImages[k] = existingStampEmptyImages[k]; });

    let cardImageData = null;
    let selectedStampPos = null;
    const stampImagesGrid = document.getElementById('stampImagesGrid');
    const stampImagesDataInput = document.getElementById('stampImagesData');
    const stampEmptyImagesDataInput = document.getElementById('stampEmptyImagesData');
    const imageDataInput = document.getElementById('imageData');

    const syncImageMapInput = (map, input) => {
        const filtered = {};
        Object.keys(map).forEach(k => { if (map[k]) filtered[k] = map[k]; });
        input.value = Object.keys(filtered).length > 0 ? JSON.stringify(filtered) : '';
    };
    const syncStampImagesInput = () => {
        syncImageMapInput(stampImages, stampImagesDataInput);
        syncImageMapInput(stampEmptyImages, stampEmptyImagesDataInput);
    };
    const syncCardImageInput = () => { imageDataInput.value = cardImageData || ''; };

    // Init stamp_images_data hidden input with existing data
    syncStampImagesInput();

    // ── Card Image Dropzone ──
    const cardDropzone = document.getElementById('cardImageDropzone');
    const cardFileInput = document.getElementById('cardImageFileInput');
    const cardDropContent = document.getElementById('cardImageDropzoneContent');
    const cardPreview = document.getElementById('cardImagePreview');
    const cardPreviewImg = document.getElementById('cardImagePreviewImg');
    const cardClearBtn = document.getElementById('cardImageClear');

    const readFileAsDataUrl = (file, callback) => {
        if (!file || !file.type.match(/^image\//)) return;
        if (file.size > 2 * 1024 * 1024) { alert('画像サイズは2MB以下にしてください'); return; }
        const r = new FileReader();
        r.onload = () => callback(r.result);
        r.readAsDataURL(file);
    };

    const setCardImage = (dataUrl) => {
        cardImageData = dataUrl;
        syncCardImageInput();
        if (dataUrl) {
            cardPreviewImg.src = dataUrl;
            cardPreview.hidden = false;
            cardDropContent.hidden = true;
        } else {
            cardPreviewImg.src = '';
            cardPreview.hidden = true;
            cardDropContent.hidden = false;
        }
        renderPreview();
    };

    cardDropzone.addEventListener('click', (e) => { if (e.target.closest('.si-dropzone-clear')) return; cardFileInput.click(); });
    cardFileInput.addEventListener('change', () => { const f = cardFileInput.files[0]; if (f) readFileAsDataUrl(f, setCardImage); });
    cardDropzone.addEventListener('dragover', (e) => { e.preventDefault(); cardDropzone.classList.add('is-dragover'); });
    cardDropzone.addEventListener('dragleave', () => cardDropzone.classList.remove('is-dragover'));
    cardDropzone.addEventListener('drop', (e) => {
        e.preventDefault(); cardDropzone.classList.remove('is-dragover');
        const f = e.dataTransfer?.files?.[0]; if (f) readFileAsDataUrl(f, setCardImage);
    });
    cardClearBtn.addEventListener('click', (e) => { e.stopPropagation(); setCardImage(null); cardFileInput.value = ''; fields.imageUrl.value = ''; });

    // ── Stamp Images Grid ──
    const renderStampGrid = () => {
        const count = Math.max(1, Math.min(999, toNumber(fields.requiredStamps.value, 20)));
        const maxShow = Math.min(count, 50);
        let html = '';
        for (let i = 1; i <= maxShow; i++) {
            const filledImg = stampImages[String(i)];
            const emptyImg = stampEmptyImages[String(i)];
            const thumb = filledImg || emptyImg;
            html += '<button type="button" class="si-grid-slot' +
                (filledImg ? ' has-image' : (emptyImg ? ' has-empty-image' : '')) +
                (selectedStampPos === i ? ' is-selected' : '') + '" data-si-pos="' + i + '">';
            if (thumb) { html += '<img src="' + esc(thumb) + '" alt="" class="si-grid-slot-img">'; }
            else { html += '<span class="si-grid-slot-num">' + i + '</span>'; }
            html += '</button>';
        }
        if (count > maxShow) html += '<span class="si-grid-overflow">他 ' + (count - maxShow) + ' 枠</span>';
        stampImagesGrid.innerHTML = html;
    };

    stampImagesGrid.addEventListener('click', (e) => {
        const slot = e.target.closest('[data-si-pos]'); if (!slot) return;
        openStampEditor(parseInt(slot.dataset.siPos, 10));
    });
    stampImagesGrid.addEventListener('dragover', (e) => {
        const slot = e.target.closest('[data-si-pos]'); if (slot) { e.preventDefault(); slot.classList.add('is-dragover'); }
    });
    stampImagesGrid.addEventListener('dragleave', (e) => {
        const slot = e.target.closest('[data-si-pos]'); if (slot) slot.classList.remove('is-dragover');
    });
    stampImagesGrid.addEventListener('drop', (e) => {
        const slot = e.target.closest('[data-si-pos]'); if (!slot) return;
        e.preventDefault(); slot.classList.remove('is-dragover');
        const pos = parseInt(slot.dataset.siPos, 10);
        const f = e.dataTransfer?.files?.[0];
        if (f) readFileAsDataUrl(f, (d) => setStampImage(stampImages, pos, d));
    });

    // ── Stamp Image Editor ──
    const editorEl = document.getElementById('stampImageEditor');
    const editorPos = document.getElementById('stampImageEditorPos');
    const editorClose = document.getElementById('stampImageEditorClose');
    const bulkApply = document.getElementById('stampImageBulkApply');
    const bulkClear = document.getElementById('stampImageBulkClear');
    const filledPane = {
        dropzone: document.getElementById('stampImageDropzone'),
        fileInput: document.getElementById('stampImageFileInput'),
        dropContent: document.getElementById('stampImageDropzoneContent'),
        preview: document.getElementById('stampImagePreview'),
        previewImg: document.getElementById('stampImagePreviewImg'),
        clear: document.getElementById('stampImageClear'),
        urlInput: document.getElementById('stampImageUrlInput'),
        urlApply: document.getElementById('stampImageUrlApply'),
    };
    const emptyPane = {
        dropzone: document.getElementById('stampEmptyImageDropzone'),
        fileInput: document.getElementById('stampEmptyImageFileInput'),
        dropContent: document.getElementById('stampEmptyImageDropzoneContent'),
        preview: document.getElementById('stampEmptyImagePreview'),
        previewImg: document.getElementById('stampEmptyImagePreviewImg'),
        clear: document.getElementById('stampEmptyImageClear'),
        urlInput: document.getElementById('stampEmptyImageUrlInput'),
        urlApply: document.getElementById('stampEmptyImageUrlApply'),
    };

    const setStampImage = (map, pos, dataUrl) => {
        if (dataUrl) map[String(pos)] = dataUrl; else delete map[String(pos)];
        syncStampImagesInput(); renderStampGrid(); renderPreview();
        if (selectedStampPos === pos) refreshStampEditor();
    };
    const refreshPane = (map, pane) => {
        const data = map[String(selectedStampPos)] || null;
        pane.urlInput.value = '';
        if (data) { pane.previewImg.src = data; pane.preview.hidden = false; pane.dropContent.hidden = true; }
        else { pane.previewImg.src = ''; pane.preview.hidden = true; pane.dropContent.hidden = false; }
    };
    const openStampEditor = (pos) => { selectedStampPos = pos; editorPos.textContent = pos; editorEl.hidden = false; refreshStampEditor(); renderStampGrid(); };
    const refreshStampEditor = () => {
        refreshPane(stampImages, filledPane);
        refreshPane(stampEmptyImages, emptyPane);
        bulkApply.disabled = !stampImages[String(selectedStampPos)] && !stampEmptyImages[String(selectedStampPos)];
    };
    const bindStampImagePane = (map, pane) => {
        pane.dropzone.addEventListener('click', (e) => { if (e.target.closest('.si-dropzone-clear')) return; pane.fileInput.click(); });
        pane.fileInput.addEventListener('change', () => { const f = pane.fileInput.files[0]; if (f && selectedStampPos) readFileAsDataUrl(f, (d) => setStampImage(map, selectedStampPos, d)); });
        pane.dropzone.addEventListener('dragover', (e) => { e.preventDefault(); pane.dropzone.classList.add('is-dragover'); });
        pane.dropzone.addEventListener('dragleave', () => pane.dropzone.classList.remove('is-dragover'));
        pane.dropzone.addEventListener('drop', (e) => {
            e.preventDefault(); pane.dropzone.classList.remove('is-dragover');
            const f = e.dataTransfer?.files?.[0]; if (f && selectedStampPos) readFileAsDataUrl(f, (d) => setStampImage(map, selectedStampPos, d));
        });
        pane.clear.addEventListener('click', (e) => { e.stopPropagation(); if (selectedStampPos) setStampImage(map, selectedStampPos, null); pane.fileInput.value = ''; });
        pane.urlApply.addEventListener('click', () => { const url = pane.urlInput.value.trim(); if (url && selectedStampPos) setStampImage(map, selectedStampPos, url); });
        pane.urlInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); pane.urlApply.click(); } });
    };
    bindStampImagePane(stampImages, filledPane);
    bindStampImagePane(stampEmptyImages, emptyPane);

    editorClose.addEventListener('click', () => { editorEl.hidden = true; selectedStampPos = null; renderStampGrid(); });
    bulkApply.addEventListener('click', () => {
        if (!selectedStampPos) return;
        const filledSrc = stampImages[String(selectedStampPos)];
        const emptySrc = stampEmptyImages[String(selectedStampPos)];
        if (!filledSrc && !emptySrc) return;
        const count = Math.max(1, Math.min(999, toNumber(fields.requiredStamps.value, 20)));
        for (let i = 1; i <= count; i++) {
            if (filledSrc) stampImages[String(i)] = filledSrc;
            if (emptySrc) stampEmptyImages[String(i)] = emptySrc;
        }
        syncStampImagesInput(); renderStampGrid(); renderPreview();
    });
    bulkClear.addEventListener('click', () => {
        Object.keys(stampImages).forEach(k => delete stampImages[k]);
        Object.keys(stampEmptyImages).forEach(k => delete stampEmptyImages[k]);
        syncStampImagesInput(); renderStampGrid(); renderPreview();
        if (selectedStampPos) refreshStampEditor();
    });

    // ── Milestones ──
    const GACHA_DESCRIPTION = 'ガチャ1回';
    const rows = () => Array.from(container.querySelectorAll('[data-milestone-row]'));
    const syncMilestoneRewardFields = (row) => {
        const type = (row.querySelector('.milestone-type-input')?.value || 'coupon');
        const couponWrap = row.querySelector('[data-reward-coupon]');
        const gachaWrap = row.querySelector('[data-reward-gacha]');
        if (couponWrap) couponWrap.hidden = type !== 'coupon';
        if (gachaWrap) gachaWrap.hidden = type !== 'gacha';
    };
    const reindex = () => {
        rows().forEach((row, index) => {
            const ci = row.querySelector('.milestone-count-input');
            const ti = row.querySelector('.milestone-type-input');
            const qi = row.querySelector('.milestone-coupon-input');
            const cl = row.querySelector('[data-label-count]');
            const tl = row.querySelector('[data-label-type]');
            const ql = row.querySelector('[data-label-coupon]');
            if (!ci || !ti || !qi) return;
            const cid = 'edit_reward_milestones_' + index + '_stamp_count';
            const tid = 'edit_reward_milestones_' + index + '_reward_type';
            const qid = 'edit_reward_milestones_' + index + '_coupon_id';
            ci.name = 'reward_milestones[' + index + '][stamp_count]'; ci.id = cid;
            ti.name = 'reward_milestones[' + index + '][reward_type]'; ti.id = tid;
            qi.name = 'reward_milestones[' + index + '][coupon_id]'; qi.id = qid;
            if (cl) cl.setAttribute('for', cid);
            if (tl) tl.setAttribute('for', tid);
            if (ql) ql.setAttribute('for', qid);
            syncMilestoneRewardFields(row);
        });
    };
    addButton.addEventListener('click', () => { container.appendChild(template.content.cloneNode(true)); reindex(); renderPreview(); });
    container.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-milestone]'); if (!btn) return;
        const row = btn.closest('[data-milestone-row]'); if (!row) return;
        if (rows().length <= 1) {
            const ci = row.querySelector('.milestone-count-input');
            const ti = row.querySelector('.milestone-type-input');
            const qi = row.querySelector('.milestone-coupon-input');
            if (ci) ci.value = '';
            if (ti) ti.value = 'coupon';
            if (qi) qi.value = '';
            syncMilestoneRewardFields(row);
            renderPreview(); return;
        }
        row.remove(); reindex(); renderPreview();
    });
    container.addEventListener('change', (e) => {
        const typeInput = e.target.closest('.milestone-type-input');
        if (!typeInput) return;
        const row = typeInput.closest('[data-milestone-row]');
        if (row) syncMilestoneRewardFields(row);
    });

    const collectMilestones = () => {
        const norm = [];
        rows().forEach(row => {
            const ci = row.querySelector('.milestone-count-input');
            const ti = row.querySelector('.milestone-type-input');
            const qi = row.querySelector('.milestone-coupon-input');
            if (!ci) return;
            const sc = toNumber(ci.value, 0);
            const type = ti ? ti.value : 'coupon';
            let desc = '';
            if (type === 'coupon' && qi && qi.value) {
                const opt = qi.options[qi.selectedIndex];
                desc = opt ? opt.textContent.trim() : '';
            } else if (type === 'gacha') {
                desc = GACHA_DESCRIPTION;
            }
            if (sc <= 0 || desc === '') return;
            norm.push({ stamp_count: sc, description: desc });
        });
        norm.sort((a, b) => a.stamp_count - b.stamp_count);
        const deduped = []; const seen = new Set();
        norm.forEach(item => { if (!seen.has(item.stamp_count)) { seen.add(item.stamp_count); deduped.push(item); } });
        return deduped;
    };

    // ── Preview ──
    const renderSlots = (required, current, layoutStyle, milestones) => {
        const cap = maxPreviewSlots[layoutStyle] || 24;
        const renderCount = Math.min(required, cap);
        const hiddenCount = Math.max(required - renderCount, 0);
        const milestoneMap = new Map(milestones.map(m => [m.stamp_count, m.description]));
        const slots = [];
        for (let i = 1; i <= renderCount; i++) {
            const filled = i <= current;
            const isMilestone = milestoneMap.has(i);
            const stampImg = filled ? stampImages[String(i)] : stampEmptyImages[String(i)];
            let content;
            if (stampImg) content = '<img src="' + esc(stampImg) + '" alt="" class="stamp-preview-slot-img">';
            else if (filled) content = '<i class="fas fa-stamp"></i>';
            else content = String(i);
            slots.push('<span class="stamp-preview-slot' + (filled ? ' is-filled' : '') + (isMilestone ? ' is-milestone' : '') + (stampImg ? ' has-image' : '') + '">' + content + (isMilestone ? '<span class="stamp-preview-slot-gift"><i class="fas fa-gift"></i></span>' : '') + '</span>');
        }
        preview.slots.className = 'stamp-preview-slots is-' + layoutStyle;
        preview.slots.innerHTML = slots.join('');
        if (hiddenCount > 0) { preview.overflow.hidden = false; preview.overflow.textContent = '他 ' + hiddenCount + ' 枠'; }
        else { preview.overflow.hidden = true; preview.overflow.textContent = ''; }
    };

    const renderMilestones = (milestones, current) => {
        if (milestones.length === 0) { preview.milestonesWrap.hidden = true; preview.milestones.innerHTML = ''; return; }
        preview.milestonesWrap.hidden = false;
        preview.milestones.innerHTML = milestones.map(m => '<li class="stamp-preview-milestone' + (current >= m.stamp_count ? ' is-achieved' : '') + '">' +
            '<span class="stamp-preview-milestone-count">' + m.stamp_count + '個</span>' +
            '<span class="stamp-preview-milestone-text">' + esc(m.description) + '</span></li>').join('');
    };

    const renderPreview = () => {
        const name = (fields.name.value || '').trim();
        const required = Math.max(1, Math.min(999, toNumber(fields.requiredStamps.value, 20)));
        const layoutStyle = fields.layoutStyle.value || 'grid';
        const validUntil = formatDate(fields.validUntil.value);
        const rewardNote = (fields.rewardDescription.value || '').trim();
        const imageUrl = cardImageData || (fields.imageUrl.value || '').trim();
        const milestones = collectMilestones().filter(m => m.stamp_count <= required);
        const current = Math.max(1, Math.round(required * 0.4));
        const progress = Math.min(100, Math.round((current / required) * 100));

        preview.name.textContent = name || 'スタンプカード名';
        preview.state.textContent = fields.isActive.checked ? '有効' : '無効';
        preview.state.classList.toggle('is-inactive', !fields.isActive.checked);
        preview.layoutLabel.textContent = getLayoutLabel();
        preview.count.textContent = current + ' / ' + required + ' スタンプ';
        preview.validity.textContent = '有効期限: ' + (validUntil || '無期限');
        preview.rewardNote.textContent = rewardNote || '補足テキストを入力するとここに表示されます。';
        preview.progressBar.style.width = progress + '%';
        preview.progress.hidden = layoutStyle === 'minimal';

        renderSlots(required, current, layoutStyle, milestones);
        renderMilestones(milestones, current);
        preview.card.className = 'stamp-preview-item is-layout-' + layoutStyle;

        if (imageUrl) { preview.image.src = imageUrl; preview.image.hidden = false; preview.imageFallback.hidden = true; }
        else { preview.image.removeAttribute('src'); preview.image.hidden = true; preview.imageFallback.hidden = false; }
    };

    preview.image.addEventListener('error', () => { preview.image.hidden = true; preview.imageFallback.hidden = false; });

    fields.requiredStamps.addEventListener('change', () => { renderStampGrid(); renderPreview(); });
    fields.requiredStamps.addEventListener('input', () => { renderStampGrid(); renderPreview(); });
    ['input', 'change'].forEach(ev => form.addEventListener(ev, renderPreview));

    reindex();
    renderStampGrid();
    renderPreview();
})();
</script>
@endsection

@push('styles')
<style>
.stamp-studio {
    --studio-bg: #f4f7fb;
    --studio-border: #dbe4ee;
    --studio-ink: #0f172a;
    --studio-muted: #5b6472;
    --studio-brand: #1d4ed8;
    --studio-brand-soft: rgba(29, 78, 216, 0.08);
    --studio-success: #047857;
    --studio-danger: #b91c1c;
    display: grid;
    grid-template-columns: minmax(0, 1.42fr) minmax(360px, 0.58fr);
    gap: 24px;
}
.stamp-studio-form-card,
.stamp-studio-preview-card { border-radius: 14px; border: 1px solid var(--studio-border); background: #fff; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }

.stamp-form-section { padding: 18px; border: 1px solid var(--studio-border); border-radius: 12px; background: #fff; }
.stamp-form-section + .stamp-form-section { margin-top: 14px; }
.stamp-form-section-head h4 { margin: 0; font-size: 15px; font-weight: 700; color: var(--studio-ink); }
.stamp-form-section-head-with-action { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.stamp-form-grid { margin-top: 14px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 16px; }
.stamp-form-col-span-2 { grid-column: 1 / -1; }
.stamp-form-check-wrap { display: flex; align-items: center; min-height: 66px; padding: 0 12px; border: 1px dashed #cad5e2; border-radius: 10px; background: #fbfdff; }
.stamp-form-note-group { margin-top: 12px; }
.stamp-form-actions { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--studio-border); }
.stamp-studio .form-label { color: #111827; font-size: 13px; font-weight: 600; }
.stamp-studio .form-control { border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02); }
.stamp-studio .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); }
.stamp-studio .form-text { margin-top: 6px; font-size: 12px; }

.btn-outline-primary { border: 1px solid #b6ccff; background: #fff; color: #1d4ed8; }
.btn-outline-primary:hover { background: #eff6ff; border-color: #7ea6ff; }
.btn-outline-danger { border: 1px solid #fecaca; background: #fff; color: #b91c1c; }
.btn-outline-danger:hover { background: #fef2f2; border-color: #fda4af; }

.stamp-milestones { margin-top: 12px; display: flex; flex-direction: column; gap: 10px; }
.stamp-milestone-row { display: grid; grid-template-columns: 110px 148px minmax(0, 1fr) auto; gap: 10px; align-items: end; padding: 12px; border: 1px solid #d7e0ea; border-radius: 11px; background: var(--studio-bg); }
.stamp-milestone-action-wrap { display: flex; align-items: flex-end; }
.stamp-milestone-gacha-note { margin: 0; font-size: 13px; color: #64748b; padding: 8px 0; }
.mt-3 { margin-top: 12px; }

.stamp-visual-subsection { margin-top: 14px; }
.stamp-visual-subsection + .stamp-visual-subsection { margin-top: 20px; padding-top: 18px; border-top: 1px solid #e5eaf0; }

.si-dropzone { position: relative; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; cursor: pointer; transition: border-color .2s, background .2s; overflow: hidden; }
.si-dropzone:hover, .si-dropzone.is-dragover { border-color: #3b82f6; background: rgba(59, 130, 246, 0.04); }
.si-file-input { position: absolute; width: 0; height: 0; opacity: 0; pointer-events: none; }
.si-dropzone-content { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 28px 16px; gap: 4px; }
.si-dropzone-icon { font-size: 1.8rem; color: #94a3b8; }
.si-dropzone-text { font-size: 13px; color: #475569; }
.si-dropzone-hint { font-size: 11px; color: #94a3b8; }
.si-dropzone-preview { position: relative; display: flex; align-items: center; justify-content: center; padding: 12px; min-height: 100px; }
.si-dropzone-preview img { max-width: 100%; max-height: 160px; object-fit: contain; border-radius: 8px; }
.si-dropzone-clear { position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 50%; border: 1px solid #e2e8f0; background: #fff; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
.si-dropzone-clear:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.si-dropzone-sm .si-dropzone-content { padding: 18px 12px; }
.si-dropzone-sm .si-dropzone-icon { font-size: 1.4rem; }
.si-dropzone-sm .si-dropzone-preview img { max-height: 100px; }
.si-url-row { margin-top: 10px; }

.stamp-images-section .form-label { margin-bottom: 4px; }
.si-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; min-height: 48px; }
.si-grid-slot { width: 48px; height: 48px; border-radius: 10px; border: 2px solid #d1d5db; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s; position: relative; padding: 0; overflow: hidden; }
.si-grid-slot:hover { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12); }
.si-grid-slot.is-selected { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2); }
.si-grid-slot.is-dragover { border-color: #3b82f6; background: #eff6ff; }
.si-grid-slot.has-image { border-color: #22c55e; }
.si-grid-slot.has-empty-image { border-color: #94a3b8; }
.si-editor-pane { display: flex; flex-direction: column; gap: 8px; }
.si-editor-pane-title { margin: 0; font-size: 12px; font-weight: 700; color: #334155; }
.si-grid-slot-num { font-size: 12px; font-weight: 700; color: #9ca3af; }
.si-grid-slot-img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
.si-grid-overflow { display: flex; align-items: center; font-size: 12px; color: #6b7280; padding: 0 8px; }

.si-editor { margin-top: 12px; padding: 14px; border: 1px solid #c7d2fe; border-radius: 12px; background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%); }
.si-editor-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #1e3a5f; }
.si-editor-close { width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; }
.si-editor-body { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.si-editor-url { display: flex; flex-direction: column; gap: 6px; }
.si-editor-url-row { display: flex; gap: 6px; }
.si-editor-url-row .form-control { flex: 1; }
.si-bulk-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }

.stamp-preview-card-header { margin-bottom: 8px; }
.stamp-preview-sub { margin: 0 0 12px; color: var(--studio-muted); font-size: 12px; }
.stamp-studio-preview-card { position: sticky; top: 90px; align-self: start; }
.stamp-preview-device { padding: 9px; border-radius: 30px; background: linear-gradient(170deg, #0b1222 0%, #1c2740 100%); box-shadow: 0 20px 34px rgba(15, 23, 42, 0.28); }
.stamp-preview-notch { width: 37%; height: 18px; margin: 2px auto 9px; border-radius: 999px; background: rgba(148, 163, 184, 0.38); }
.stamp-preview-screen { border-radius: 21px; min-height: 610px; padding: 16px 12px; background: linear-gradient(180deg, #f8fafc 0%, #edf2f7 100%); }
.stamp-preview-screen-title { margin: 0 0 10px; color: #475569; font-size: 12px; font-weight: 600; }
.stamp-preview-item { border: 1px solid #d6e0ea; border-radius: 18px; padding: 13px; background: #fff; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07); }
.stamp-preview-image-wrap { margin-bottom: 12px; border-radius: 12px; overflow: hidden; min-height: 128px; background: #e2e8f0; }
.stamp-preview-image { display: block; width: 100%; height: 128px; object-fit: cover; }
.stamp-preview-image-fallback { min-height: 128px; display: flex; align-items: center; justify-content: center; color: #1d4ed8; font-size: 2rem; }
.stamp-preview-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.stamp-preview-name { margin: 0; color: var(--studio-ink); font-size: 16px; font-weight: 700; line-height: 1.35; }
.stamp-preview-state { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; border: 1px solid #a7f3d0; color: var(--studio-success); background: #ecfdf5; }
.stamp-preview-state.is-inactive { border-color: #fecaca; color: var(--studio-danger); background: #fef2f2; }
.stamp-preview-layout { margin: 8px 0 11px; color: #4b5563; font-size: 12px; font-weight: 600; }
.stamp-preview-slots { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
.stamp-preview-slot { width: 34px; height: 34px; position: relative; border-radius: 50%; border: 2px solid #d6e0ea; background: #f8fafc; color: #6b7280; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; overflow: hidden; }
.stamp-preview-slot.is-filled { color: #fff; border-color: #1d4ed8; background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 100%); }
.stamp-preview-slot.has-image { border-color: #22c55e; background: #fff; padding: 0; }
.stamp-preview-slot.has-image.is-filled { border-color: #1d4ed8; }
.stamp-preview-slot-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.stamp-preview-slot.is-milestone { box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.14); }
.stamp-preview-slot-gift { position: absolute; top: -6px; right: -4px; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #fff; background: #f97316; color: #fff; font-size: 8px; display: inline-flex; align-items: center; justify-content: center; }
.stamp-preview-slots.is-grid { max-width: 264px; margin: 0 auto; }
.stamp-preview-slots.is-grid .stamp-preview-slot { width: 38px; height: 38px; font-size: 13px; }
.stamp-preview-slots.is-row { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; padding-bottom: 4px; }
.stamp-preview-slots.is-row .stamp-preview-slot { width: 30px; height: 30px; flex-shrink: 0; font-size: 10px; }
.stamp-preview-slots.is-circle { display: grid; grid-template-columns: repeat(5, 1fr); max-width: 196px; margin: 0 auto; }
.stamp-preview-slots.is-circle .stamp-preview-slot { width: 33px; height: 33px; margin: 0 auto; }
.stamp-preview-item.is-layout-minimal .stamp-preview-slots { gap: 4px; }
.stamp-preview-item.is-layout-minimal .stamp-preview-slot { width: 22px; height: 22px; font-size: 9px; }
.stamp-preview-item.is-layout-card .stamp-preview-slots { padding: 10px; border-radius: 12px; border: 1px solid #d6e0ea; background: #f8fafc; }
.stamp-preview-overflow { margin: 8px 0 0; font-size: 12px; color: #6b7280; }
.stamp-preview-progress { margin: 12px 0 8px; height: 9px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.stamp-preview-progress-bar { width: 0; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%); }
.stamp-preview-meta { margin-bottom: 10px; }
.stamp-preview-count { margin: 0 0 2px; color: #374151; font-size: 13px; }
.stamp-preview-validity { margin: 0; color: #1d4ed8; font-size: 12px; }
.stamp-preview-milestones-wrap { margin-bottom: 10px; }
.stamp-preview-milestones-title { margin: 0 0 7px; color: #111827; font-size: 12px; font-weight: 700; }
.stamp-preview-milestones { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.stamp-preview-milestone { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border: 1px solid #d6e0ea; border-radius: 9px; background: #f8fafc; font-size: 12px; }
.stamp-preview-milestone.is-achieved { border-color: #a7f3d0; background: #ecfdf5; }
.stamp-preview-milestone-count { min-width: 46px; padding: 2px 8px; border-radius: 999px; text-align: center; font-weight: 700; color: #111827; background: #e2e8f0; }
.stamp-preview-milestone.is-achieved .stamp-preview-milestone-count { color: #fff; background: #059669; }
.stamp-preview-milestone-text { color: #334155; line-height: 1.4; }
.stamp-preview-reward-note { margin: 0; color: #6b7280; font-size: 12px; line-height: 1.55; }

@media (max-width: 1280px) {
    .stamp-studio { grid-template-columns: 1fr; }
    .stamp-studio-preview-card { position: static; }
    .stamp-preview-screen { min-height: 0; }
}
@media (max-width: 780px) {
    .stamp-form-grid { grid-template-columns: 1fr; }
    .stamp-form-col-span-2 { grid-column: auto; }
    .stamp-milestone-row { grid-template-columns: 1fr; }
    .stamp-milestone-action-wrap { justify-content: flex-end; }
    .si-editor-body { grid-template-columns: 1fr; }
    .si-grid-slot { width: 40px; height: 40px; }
}
</style>
@endpush
