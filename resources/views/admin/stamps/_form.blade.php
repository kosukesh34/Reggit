@php
    $stampCard = $stampCard ?? null;
    $isEdit = $stampCard !== null;
    $layoutOptions = \App\Models\StampCard::getLayoutOptions();
    $milestones = old('reward_milestones', $isEdit ? ($stampCard->reward_milestones ?? [['stamp_count' => '']]) : [['stamp_count' => '']]);
    if (! is_array($milestones) || $milestones === []) {
        $milestones = [['stamp_count' => '']];
    } else {
        $milestones = array_values(array_filter($milestones, static function ($milestone): bool {
            if (! is_array($milestone)) {
                return false;
            }
            $rewardType = (string) ($milestone['reward_type'] ?? '');

            return $rewardType === '' || in_array($rewardType, \App\Enums\StampRewardType::keys(), true);
        }));
        if ($milestones === []) {
            $milestones = [['stamp_count' => '']];
        }
    }
    $existingStampImages = $isEdit ? ($stampCard->stamp_images ?? []) : [];
    $existingStampEmptyImages = $isEdit ? ($stampCard->stamp_empty_images ?? []) : [];
    $cardImageUrl = old('image_url', $isEdit ? $stampCard->image_url : null);
@endphp

<div class="stamp-studio">
    <section class="card stamp-studio-form-card">
        @if($isEdit)
        <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp"></i> スタンプカード編集</h3></div>
        @endif
        <div class="card-body">
            <form
                action="{{ $formAction }}"
                method="POST"
                data-stamp-form
            >
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif
                <input type="hidden" name="valid_days" value="">

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head"><h4>基本情報</h4></div>
                    <div class="stamp-form-grid">
                        <div class="form-group stamp-form-col-span-2">
                            <label for="name" class="form-label">カード名 <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $isEdit ? $stampCard->name : null) }}" required maxlength="255" @if(! $isEdit) placeholder="例）来店スタンプカード" @endif>
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="required_stamps" class="form-label">必要スタンプ数 <span class="text-danger">*</span></label>
                            <input type="number" name="required_stamps" id="required_stamps" class="form-control" value="{{ old('required_stamps', $isEdit ? $stampCard->required_stamps : 20) }}" min="1" max="999" required>
                            @if(! $isEdit)
                            <p class="form-text text-muted">達成条件（1〜999）</p>
                            @endif
                            @error('required_stamps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="layout_style" class="form-label">スタンプの並べ方</label>
                            <select name="layout_style" id="layout_style" class="form-control">
                                @foreach($layoutOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('layout_style', $isEdit ? ($stampCard->layout_style ?? 'grid') : 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        @include('admin.stamps._design_field', ['stampCard' => $stampCard])
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head"><h4>有効期間・公開</h4></div>
                    <div class="stamp-form-grid">
                        <div class="form-group">
                            <label for="valid_until" class="form-label">有効期限（日付）</label>
                            <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until', $isEdit ? $stampCard->valid_until?->format('Y-m-d') : null) }}">
                            <p class="form-text text-muted">未設定の場合は無期限</p>
                            @error('valid_until')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group form-check stamp-form-check-wrap">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', $isEdit ? $stampCard->is_active : true)) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">有効（公開）</label>
                        </div>
                    </div>
                </div>

                @include('admin.stamps._cycle_fields', ['stampCard' => $stampCard, 'cycleTargetCards' => $view->cycleTargetCards ?? collect()])

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head stamp-form-section-head-with-action">
                        <h4>達成特典（複数設定可）</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMilestoneButton">
                            <i class="fas fa-plus"></i> 特典を追加
                        </button>
                    </div>
                    <div class="stamp-milestones" id="milestonesContainer">
                        @foreach($milestones as $index => $milestone)
                            @include('admin.stamps._milestone_row', [
                                'index' => $index,
                                'milestone' => is_array($milestone) ? $milestone : [],
                                'coupons' => $view->coupons,
                                'isNamed' => true,
                                'idPrefix' => 'reward_milestones',
                            ])
                        @endforeach
                    </div>
                    <p class="form-text text-muted">
                        @if($isEdit)
                            到達時にクーポンまたはガチャ権利が自動で付与されます。
                        @else
                            例: 10個でクーポン、20個でガチャ のように段階特典を設定できます。到達時に自動で付与されます。
                        @endif
                    </p>
                    @if(($view->isGachaEnabled ?? false) !== true)
                    <p class="form-text text-muted">ガチャ特典を使う場合は、設定のガチャを有効にしてください。</p>
                    @endif
                    @error('reward_milestones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.stamp_count')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.reward_type')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror

                    <div class="form-group stamp-form-col-span-2 stamp-form-note-group">
                        <label for="reward_description" class="form-label">{{ $isEdit ? '補足テキスト' : '補足テキスト（任意）' }}</label>
                        <input type="text" name="reward_description" id="reward_description" class="form-control" value="{{ old('reward_description', $isEdit ? $stampCard->reward_description : null) }}" maxlength="500" @if(! $isEdit) placeholder="例）特典引き換えはスタッフへお声がけください" @endif>
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
                            <div class="si-dropzone-content" id="cardImageDropzoneContent" @if($cardImageUrl) hidden @endif>
                                <span class="si-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                <span class="si-dropzone-text">クリックまたはドラッグ＆ドロップ</span>
                                <span class="si-dropzone-hint">PNG / JPG 2MBまで</span>
                            </div>
                            <div class="si-dropzone-preview" id="cardImagePreview" @if(! $cardImageUrl) hidden @endif>
                                <img id="cardImagePreviewImg" src="{{ $cardImageUrl ?? '' }}" alt="">
                                <button type="button" class="si-dropzone-clear" id="cardImageClear"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div class="si-url-row">
                            <label for="image_url" class="form-label small">または URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" value="{{ $cardImageUrl }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                        </div>
                        <input type="hidden" name="image_data" id="imageData">
                        @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="stamp-visual-subsection">
                        <label class="form-label">個別スタンプ画像</label>
                        @if($isEdit)
                            <p data-help-visible class="form-text text-muted">押す前と押したあとの画像を、全スタンプを並べて設定できます。設定済み: 押す前 {{ count($existingStampEmptyImages) }}件・押したあと {{ count($existingStampImages) }}件</p>
                            <a href="{{ admin_route('admin.stamps.images', ['id' => $stampCard->id]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-images"></i> スタンプ画像を設定</a>
                        @else
                            <p class="form-text text-muted">押す前と押したあとの画像は、追加したあとに開く「スタンプ画像」の画面で、全スタンプを並べて設定できます。</p>
                        @endif
                    </div>
                </div>

                <div class="form-actions stamp-form-actions">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                    <x-back-link :href="admin_route('admin.stamps')" label="キャンセル" />
                </div>
            </form>

            @if($isEdit)
            <form action="{{ admin_route('admin.stamps.delete', ['id' => $stampCard->id]) }}" method="POST" class="mt-3" onsubmit="return confirm('このスタンプカードを削除しますか？');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">削除</button>
            </form>
            @endif
        </div>
    </section>

    <aside class="card stamp-studio-preview-card">
        <div class="card-header stamp-preview-card-header">
            <h3 class="card-title"><i class="fas fa-wand-magic-sparkles"></i> ライブプレビュー</h3>
        </div>
        <div class="card-body">
            <x-admin.app-preview id="stampLivePreview" title="実際の会員アプリのスタンプカード画面" path="/stamp" kind="stamp" />
        </div>
    </aside>
</div>

<template id="milestoneRowTemplate">
    @include('admin.stamps._milestone_row', [
        'index' => 0,
        'milestone' => [],
        'coupons' => $view->coupons,
        'isNamed' => false,
        'idPrefix' => 'reward_milestones',
    ])
</template>

<script>
(() => {
    const form = document.querySelector('[data-stamp-form]');
    if (!form) return;

    const milestonesContainer = document.getElementById('milestonesContainer');
    const addMilestoneButton = document.getElementById('addMilestoneButton');
    const milestoneRowTemplate = document.getElementById('milestoneRowTemplate');

    const fields = {
        name: form.querySelector('#name'),
        requiredStamps: form.querySelector('#required_stamps'),
        layoutStyle: form.querySelector('#layout_style'),
        validUntil: form.querySelector('#valid_until'),
        rewardDescription: form.querySelector('#reward_description'),
        imageUrl: form.querySelector('#image_url'),
    };

    const previewFrame = document.getElementById('stampLivePreview');
    const toNumber = (v, fb) => { const n = parseInt(v, 10); return isNaN(n) ? fb : n; };

    // 個別スタンプ画像は別画面で設定するため、プレビューには保存済みのものを使う
    const stampImages = @json((object) $existingStampImages);
    const stampEmptyImages = @json((object) $existingStampEmptyImages);

    let cardImageData = null;
    const imageDataInput = document.getElementById('imageData');
    const syncCardImageInput = () => { imageDataInput.value = cardImageData || ''; };

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

    cardDropzone.addEventListener('click', (e) => {
        if (e.target.closest('.si-dropzone-clear')) return;
        cardFileInput.click();
    });
    cardFileInput.addEventListener('change', () => {
        const f = cardFileInput.files[0];
        if (f) readFileAsDataUrl(f, setCardImage);
    });
    cardDropzone.addEventListener('dragover', (e) => { e.preventDefault(); cardDropzone.classList.add('is-dragover'); });
    cardDropzone.addEventListener('dragleave', () => cardDropzone.classList.remove('is-dragover'));
    cardDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        cardDropzone.classList.remove('is-dragover');
        const f = e.dataTransfer?.files?.[0];
        if (f) readFileAsDataUrl(f, setCardImage);
    });
    cardClearBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        setCardImage(null);
        cardFileInput.value = '';
        if (fields.imageUrl) fields.imageUrl.value = '';
    });

    const getMilestoneRows = () => Array.from(milestonesContainer.querySelectorAll('[data-milestone-row]'));
    const syncMilestoneRewardFields = (row) => {
        const type = (row.querySelector('.milestone-type-input')?.value || 'coupon');
        const couponWrap = row.querySelector('[data-reward-coupon]');
        const gachaWrap = row.querySelector('[data-reward-gacha]');
        if (couponWrap) couponWrap.hidden = type !== 'coupon';
        if (gachaWrap) gachaWrap.hidden = type !== 'gacha';
    };
    const reindexMilestoneRows = () => {
        getMilestoneRows().forEach((row, index) => {
            const ci = row.querySelector('.milestone-count-input');
            const ti = row.querySelector('.milestone-type-input');
            const qi = row.querySelector('.milestone-coupon-input');
            const cl = row.querySelector('[data-label-count]');
            const tl = row.querySelector('[data-label-type]');
            const ql = row.querySelector('[data-label-coupon]');
            if (!ci || !ti || !qi) return;
            const cid = 'reward_milestones_' + index + '_stamp_count';
            const tid = 'reward_milestones_' + index + '_reward_type';
            const qid = 'reward_milestones_' + index + '_coupon_id';
            ci.name = 'reward_milestones[' + index + '][stamp_count]'; ci.id = cid;
            ti.name = 'reward_milestones[' + index + '][reward_type]'; ti.id = tid;
            qi.name = 'reward_milestones[' + index + '][coupon_id]'; qi.id = qid;
            if (cl) cl.setAttribute('for', cid);
            if (tl) tl.setAttribute('for', tid);
            if (ql) ql.setAttribute('for', qid);
            syncMilestoneRewardFields(row);
        });
    };
    const addMilestoneRow = () => {
        milestonesContainer.appendChild(milestoneRowTemplate.content.cloneNode(true));
        reindexMilestoneRows();
        renderPreview();
    };
    const removeMilestoneRow = (row) => {
        if (getMilestoneRows().length <= 1) {
            const ci = row.querySelector('.milestone-count-input');
            const ti = row.querySelector('.milestone-type-input');
            const qi = row.querySelector('.milestone-coupon-input');
            if (ci) ci.value = '';
            if (ti) ti.value = 'coupon';
            if (qi) qi.value = '';
            syncMilestoneRewardFields(row);
            renderPreview();
            return;
        }
        row.remove();
        reindexMilestoneRows();
        renderPreview();
    };
    addMilestoneButton.addEventListener('click', addMilestoneRow);
    milestonesContainer.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-milestone]');
        if (!btn) return;
        const row = btn.closest('[data-milestone-row]');
        if (row) removeMilestoneRow(row);
    });
    milestonesContainer.addEventListener('change', (e) => {
        const typeInput = e.target.closest('.milestone-type-input');
        if (!typeInput) return;
        const row = typeInput.closest('[data-milestone-row]');
        if (row) syncMilestoneRewardFields(row);
    });

    const GACHA_DESCRIPTION = 'ガチャ1回';
    const collectMilestones = () => {
        const norm = [];
        getMilestoneRows().forEach((row) => {
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
        return norm.sort((a, b) => a.stamp_count - b.stamp_count);
    };

    const renderPreview = () => {
        const required = Math.max(1, Math.min(999, toNumber(fields.requiredStamps.value, 20)));
        previewFrame?.dispatchEvent(new CustomEvent('app-preview:draft', { detail: {
            id: 'preview',
            name: (fields.name.value || '').trim() || 'スタンプカード名',
            required_stamps: required,
            current_stamps: Math.max(1, Math.round(required * 0.4)),
            image_url: cardImageData || (fields.imageUrl.value || '').trim() || null,
            reward_description: (fields.rewardDescription.value || '').trim() || null,
            layout_style: fields.layoutStyle.value || 'grid',
            design: form.querySelector('[name="design"]:checked')?.value || 'standard',
            valid_until: fields.validUntil.value || null,
            reward_milestones: collectMilestones(),
            stamp_images: stampImages,
            stamp_empty_images: stampEmptyImages,
        } }));
    };

    ['input', 'change'].forEach(ev => form.addEventListener(ev, renderPreview));

    reindexMilestoneRows();
    renderPreview();
})();
</script>
