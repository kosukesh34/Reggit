@extends('admin.layout')
@section('title', 'スタンプカード追加')
@section('page-title', 'スタンプカード追加')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>
@php($layoutOptions = \App\Models\StampCard::getLayoutOptions())
@php($oldMilestones = old('reward_milestones', [['stamp_count' => '']]))
@if(!is_array($oldMilestones) || $oldMilestones === [])
    @php($oldMilestones = [['stamp_count' => '']])
@endif

<div class="stamp-studio">
    <section class="card stamp-studio-form-card">
        <div class="stamp-studio-banner">
            <div>
                <p class="stamp-studio-kicker">Stamp Card Studio</p>
                <h3 class="stamp-studio-title">体験に直結するスタンプカードを設計</h3>
                <p class="stamp-studio-desc">入力内容は右側のプレビューへ即時反映されます。達成特典は複数設定できます。</p>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ admin_route('admin.stamps.store') }}" method="POST" data-stamp-create-form>
                @csrf
                <input type="hidden" name="valid_days" value="">

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head">
                        <h4>基本情報</h4>
                    </div>
                    <div class="stamp-form-grid">
                        <div class="form-group stamp-form-col-span-2">
                            <label for="name" class="form-label">カード名 <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required maxlength="255" placeholder="例）来店スタンプカード">
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="required_stamps" class="form-label">必要スタンプ数 <span class="text-danger">*</span></label>
                            <input type="number" name="required_stamps" id="required_stamps" class="form-control" value="{{ old('required_stamps', 20) }}" min="1" max="999" required>
                            <p class="form-text text-muted">達成条件（1〜999）</p>
                            @error('required_stamps')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label for="layout_style" class="form-label">スタンプの並べ方</label>
                            <select name="layout_style" id="layout_style" class="form-control">
                                @foreach($layoutOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('layout_style', 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        @include('admin.stamps._design_field', ['stampCard' => null])
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head">
                        <h4>有効期間・公開</h4>
                    </div>
                    <div class="stamp-form-grid">
                        <div class="form-group">
                            <label for="valid_until" class="form-label">有効期限（日付）</label>
                            <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until') }}">
                            <p class="form-text text-muted">未設定の場合は無期限</p>
                            @error('valid_until')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group form-check stamp-form-check-wrap">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', true)) ? 'checked' : '' }}>
                            <label for="is_active" class="form-check-label">有効（公開）</label>
                        </div>
                    </div>
                </div>

                @include('admin.stamps._cycle_fields', ['stampCard' => null, 'cycleTargetCards' => $view->cycleTargetCards ?? collect()])

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head stamp-form-section-head-with-action">
                        <h4>達成特典（複数設定可）</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMilestoneButton">
                            <i class="fas fa-plus"></i> 特典を追加
                        </button>
                    </div>
                    <div class="stamp-milestones" id="milestonesContainer">
                        @foreach($oldMilestones as $index => $milestone)
                            @include('admin.stamps._milestone_row', [
                                'index' => $index,
                                'milestone' => is_array($milestone) ? $milestone : [],
                                'coupons' => $view->coupons,
                                'isNamed' => true,
                                'idPrefix' => 'reward_milestones',
                            ])
                        @endforeach
                    </div>
                    <p class="form-text text-muted">例: 10個でクーポン、20個でガチャ のように段階特典を設定できます。到達時に自動で付与されます。</p>
                    @if(($view->isGachaEnabled ?? false) !== true)
                    <p class="form-text text-muted">ガチャ特典を使う場合は、設定のガチャを有効にしてください。</p>
                    @endif
                    @error('reward_milestones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.stamp_count')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.reward_type')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.coupon_id')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror

                    <div class="form-group stamp-form-col-span-2 stamp-form-note-group">
                        <label for="reward_description" class="form-label">補足テキスト（任意）</label>
                        <input type="text" name="reward_description" id="reward_description" class="form-control" value="{{ old('reward_description') }}" maxlength="500" placeholder="例）特典引き換えはスタッフへお声がけください">
                        @error('reward_description')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head">
                        <h4>ビジュアル</h4>
                    </div>

                    <div class="stamp-visual-subsection">
                        <label class="form-label">カード画像</label>
                        <p class="form-text text-muted">ドラッグ＆ドロップまたはURLで画像を設定できます。</p>
                        <div class="si-dropzone" id="cardImageDropzone" tabindex="0">
                            <input type="file" class="si-file-input" id="cardImageFileInput" accept="image/png,image/jpeg,image/gif,image/webp">
                            <div class="si-dropzone-content" id="cardImageDropzoneContent">
                                <span class="si-dropzone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                                <span class="si-dropzone-text">クリックまたはドラッグ＆ドロップ</span>
                                <span class="si-dropzone-hint">PNG / JPG 2MBまで</span>
                            </div>
                            <div class="si-dropzone-preview" id="cardImagePreview" hidden>
                                <img id="cardImagePreviewImg" src="" alt="">
                                <button type="button" class="si-dropzone-clear" id="cardImageClear"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div class="si-url-row">
                            <label for="image_url" class="form-label small">または URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                        </div>
                        <input type="hidden" name="image_data" id="imageData">
                        @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="stamp-visual-subsection">
                        <label class="form-label">個別スタンプ画像</label>
                        <p class="form-text text-muted">押す前と押したあとの画像は、追加したあとに開く「スタンプ画像」の画面で、全スタンプを並べて設定できます。</p>
                    </div>
                </div>

                <div class="form-actions stamp-form-actions">
                    <button type="submit" class="btn btn-primary">追加</button>
                    <x-back-link :href="admin_route('admin.stamps')" label="キャンセル" />
                </div>
            </form>
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
    const form = document.querySelector('[data-stamp-create-form]');
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

    // ── Images ──
    let cardImageData = null;
    const imageDataInput = document.getElementById('imageData');

    const syncCardImageInput = () => {
        imageDataInput.value = cardImageData || '';
    };

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
    cardClearBtn.addEventListener('click', (e) => { e.stopPropagation(); setCardImage(null); cardFileInput.value = ''; });

    // ── Milestones ──
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
            renderPreview(); return;
        }
        row.remove(); reindexMilestoneRows(); renderPreview();
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

    // ── Preview ──
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
        } }));
    };

    ['input', 'change'].forEach(ev => form.addEventListener(ev, renderPreview));

    reindexMilestoneRows();
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
.stamp-studio-banner { margin: 0 0 12px; padding: 20px 22px; border: 1px solid #d6e2ff; border-radius: 12px; background: radial-gradient(circle at 90% 12%, rgba(29, 78, 216, 0.16) 0%, rgba(29, 78, 216, 0) 50%), linear-gradient(180deg, #f7faff 0%, #eef4ff 100%); }
.stamp-studio-kicker { margin: 0 0 8px; color: #335aa6; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.stamp-studio-title { margin: 0 0 6px; color: var(--studio-ink); font-size: 24px; font-weight: 700; line-height: 1.35; }
.stamp-studio-desc { margin: 0; color: var(--studio-muted); font-size: 13px; line-height: 1.7; }

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

/* ── Visual / Dropzone ── */
.stamp-visual-subsection { margin-top: 14px; }
.stamp-visual-subsection + .stamp-visual-subsection { margin-top: 20px; padding-top: 18px; border-top: 1px solid #e5eaf0; }

.si-url-row { margin-top: 10px; }

/* ── Stamp Images Grid ── */

/* ── Stamp Image Editor ── */


/* ── Preview ── */
.stamp-preview-card-header { margin-bottom: 8px; }
.stamp-studio-preview-card { position: sticky; top: 90px; align-self: start; }

@media (max-width: 1280px) {
    .stamp-studio { grid-template-columns: 1fr; }
    .stamp-studio-preview-card { position: static; }
}
@media (max-width: 780px) {
    .stamp-form-grid { grid-template-columns: 1fr; }
    .stamp-form-col-span-2 { grid-column: auto; }
    .stamp-milestone-row { grid-template-columns: 1fr; }
    .stamp-milestone-action-wrap { justify-content: flex-end; }
}
</style>
@endpush
