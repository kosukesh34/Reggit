@extends('admin.layout')
@section('title', 'スタンプカード追加')
@section('page-title', 'スタンプカード追加')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>
@php($layoutOptions = \App\Models\StampCard::getLayoutOptions())
@php($oldMilestones = old('reward_milestones', [['stamp_count' => '', 'description' => '']]))
@if(!is_array($oldMilestones) || $oldMilestones === [])
    @php($oldMilestones = [['stamp_count' => '', 'description' => '']])
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
                            <label for="layout_style" class="form-label">レイアウト</label>
                            <select name="layout_style" id="layout_style" class="form-control">
                                @foreach($layoutOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('layout_style', 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
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

                <div class="stamp-form-section">
                    <div class="stamp-form-section-head stamp-form-section-head-with-action">
                        <h4>達成特典（複数設定可）</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addMilestoneButton">
                            <i class="fas fa-plus"></i> 特典を追加
                        </button>
                    </div>
                    <div class="stamp-milestones" id="milestonesContainer">
                        @foreach($oldMilestones as $index => $milestone)
                        <div class="stamp-milestone-row" data-milestone-row>
                            <div class="stamp-milestone-count-wrap">
                                <label class="form-label" for="reward_milestones_{{ $index }}_stamp_count">スタンプ数</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="999"
                                    class="form-control milestone-count-input"
                                    id="reward_milestones_{{ $index }}_stamp_count"
                                    name="reward_milestones[{{ $index }}][stamp_count]"
                                    value="{{ is_array($milestone) ? ($milestone['stamp_count'] ?? '') : '' }}"
                                    placeholder="10"
                                >
                            </div>
                            <div class="stamp-milestone-reward-wrap">
                                <label class="form-label" for="reward_milestones_{{ $index }}_description">特典内容</label>
                                <input
                                    type="text"
                                    maxlength="500"
                                    class="form-control milestone-description-input"
                                    id="reward_milestones_{{ $index }}_description"
                                    name="reward_milestones[{{ $index }}][description]"
                                    value="{{ is_array($milestone) ? ($milestone['description'] ?? '') : '' }}"
                                    placeholder="例）ドリンク1杯無料"
                                >
                            </div>
                            <div class="stamp-milestone-action-wrap">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-milestone aria-label="特典を削除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <p class="form-text text-muted">例: 10個で特典A、20個で特典B のように段階特典を設定できます。</p>
                    @error('reward_milestones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.stamp_count')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    @error('reward_milestones.*.description')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror

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
                    <div class="stamp-form-grid">
                        <div class="form-group stamp-form-col-span-2">
                            <label for="image_url" class="form-label">画像URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                            <p class="form-text text-muted">未入力の場合はアイコン表示</p>
                            @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-actions stamp-form-actions">
                    <button type="submit" class="btn btn-primary">追加</button>
                    <a href="{{ admin_route('admin.stamps') }}" class="btn btn-secondary">キャンセル</a>
                </div>
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
                            <div class="stamp-preview-image-fallback" id="stampPreviewImageFallback">
                                <i class="fas fa-stamp" aria-hidden="true"></i>
                            </div>
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

<template id="milestoneRowTemplate">
    <div class="stamp-milestone-row" data-milestone-row>
        <div class="stamp-milestone-count-wrap">
            <label class="form-label" data-label-count>スタンプ数</label>
            <input type="number" min="1" max="999" class="form-control milestone-count-input" placeholder="10">
        </div>
        <div class="stamp-milestone-reward-wrap">
            <label class="form-label" data-label-description>特典内容</label>
            <input type="text" maxlength="500" class="form-control milestone-description-input" placeholder="例）ドリンク1杯無料">
        </div>
        <div class="stamp-milestone-action-wrap">
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-milestone aria-label="特典を削除">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
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

    const maxPreviewSlots = {
        grid: 24,
        row: 24,
        circle: 25,
        minimal: 40,
        card: 24,
    };

    const toNumber = (value, fallback) => {
        const n = Number.parseInt(value, 10);
        return Number.isNaN(n) ? fallback : n;
    };

    const formatDate = (value) => {
        if (!value) return null;
        const d = new Date(value + 'T00:00:00');
        if (Number.isNaN(d.getTime())) return null;
        return d.toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    };

    const getLayoutLabel = () => {
        const selected = fields.layoutStyle.options[fields.layoutStyle.selectedIndex];
        return selected ? selected.textContent.trim() : 'グリッド（2列）';
    };

    const getMilestoneRows = () => Array.from(milestonesContainer.querySelectorAll('[data-milestone-row]'));

    const reindexMilestoneRows = () => {
        getMilestoneRows().forEach((row, index) => {
            const countInput = row.querySelector('.milestone-count-input');
            const descriptionInput = row.querySelector('.milestone-description-input');
            const countLabel = row.querySelector('[data-label-count]') || row.querySelector('.stamp-milestone-count-wrap .form-label');
            const descriptionLabel = row.querySelector('[data-label-description]') || row.querySelector('.stamp-milestone-reward-wrap .form-label');
            if (!countInput || !descriptionInput) return;

            const countId = 'reward_milestones_' + index + '_stamp_count';
            const descriptionId = 'reward_milestones_' + index + '_description';

            countInput.name = 'reward_milestones[' + index + '][stamp_count]';
            countInput.id = countId;
            descriptionInput.name = 'reward_milestones[' + index + '][description]';
            descriptionInput.id = descriptionId;

            if (countLabel) countLabel.setAttribute('for', countId);
            if (descriptionLabel) descriptionLabel.setAttribute('for', descriptionId);
        });
    };

    const addMilestoneRow = (stampCount = '', description = '') => {
        const fragment = milestoneRowTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-milestone-row]');
        if (!row) return;

        const countInput = row.querySelector('.milestone-count-input');
        const descriptionInput = row.querySelector('.milestone-description-input');
        if (countInput) countInput.value = stampCount;
        if (descriptionInput) descriptionInput.value = description;

        milestonesContainer.appendChild(fragment);
        reindexMilestoneRows();
        renderPreview();
    };

    const removeMilestoneRow = (row) => {
        const rows = getMilestoneRows();
        if (rows.length <= 1) {
            const countInput = row.querySelector('.milestone-count-input');
            const descriptionInput = row.querySelector('.milestone-description-input');
            if (countInput) countInput.value = '';
            if (descriptionInput) descriptionInput.value = '';
            renderPreview();
            return;
        }
        row.remove();
        reindexMilestoneRows();
        renderPreview();
    };

    const collectMilestones = () => {
        const normalized = [];
        getMilestoneRows().forEach((row) => {
            const countInput = row.querySelector('.milestone-count-input');
            const descriptionInput = row.querySelector('.milestone-description-input');
            if (!countInput || !descriptionInput) return;

            const stampCount = toNumber(countInput.value, 0);
            const description = (descriptionInput.value || '').trim();
            if (stampCount <= 0 || description === '') return;
            normalized.push({ stamp_count: stampCount, description });
        });

        normalized.sort((a, b) => a.stamp_count - b.stamp_count);

        const deduped = [];
        const seen = new Set();
        normalized.forEach((item) => {
            if (seen.has(item.stamp_count)) return;
            seen.add(item.stamp_count);
            deduped.push(item);
        });

        return deduped;
    };

    const renderSlots = (required, current, layoutStyle, milestones) => {
        const cap = maxPreviewSlots[layoutStyle] || 24;
        const renderCount = Math.min(required, cap);
        const hiddenCount = Math.max(required - renderCount, 0);
        const milestoneMap = new Map(milestones.map((m) => [m.stamp_count, m.description]));
        const slots = [];

        for (let i = 1; i <= renderCount; i += 1) {
            const filled = i <= current;
            const isMilestone = milestoneMap.has(i);
            slots.push(
                '<span class="stamp-preview-slot' +
                    (filled ? ' is-filled' : '') +
                    (isMilestone ? ' is-milestone' : '') +
                    '" aria-hidden="true">' +
                    (filled ? '<i class="fas fa-stamp"></i>' : i) +
                    (isMilestone ? '<span class="stamp-preview-slot-gift"><i class="fas fa-gift"></i></span>' : '') +
                '</span>'
            );
        }

        preview.slots.className = 'stamp-preview-slots is-' + layoutStyle;
        preview.slots.innerHTML = slots.join('');

        if (hiddenCount > 0) {
            preview.overflow.hidden = false;
            preview.overflow.textContent = '他 ' + hiddenCount + ' 枠';
        } else {
            preview.overflow.hidden = true;
            preview.overflow.textContent = '';
        }
    };

    const renderMilestones = (milestones, current) => {
        if (milestones.length === 0) {
            preview.milestonesWrap.hidden = true;
            preview.milestones.innerHTML = '';
            return;
        }

        const html = milestones.map((m) => {
            const achieved = current >= m.stamp_count;
            return (
                '<li class="stamp-preview-milestone' + (achieved ? ' is-achieved' : '') + '">' +
                    '<span class="stamp-preview-milestone-count">' + m.stamp_count + '個</span>' +
                    '<span class="stamp-preview-milestone-text">' + m.description.replace(/[&<>"]/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch])) + '</span>' +
                '</li>'
            );
        }).join('');

        preview.milestonesWrap.hidden = false;
        preview.milestones.innerHTML = html;
    };

    const renderPreview = () => {
        const name = (fields.name.value || '').trim();
        const required = Math.max(1, Math.min(999, toNumber(fields.requiredStamps.value, 20)));
        const layoutStyle = fields.layoutStyle.value || 'grid';
        const validUntil = formatDate(fields.validUntil.value);
        const rewardNote = (fields.rewardDescription.value || '').trim();
        const imageUrl = (fields.imageUrl.value || '').trim();
        const milestones = collectMilestones().filter((m) => m.stamp_count <= required);
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

        if (imageUrl) {
            preview.image.src = imageUrl;
            preview.image.hidden = false;
            preview.imageFallback.hidden = true;
        } else {
            preview.image.removeAttribute('src');
            preview.image.hidden = true;
            preview.imageFallback.hidden = false;
        }
    };

    preview.image.addEventListener('error', () => {
        preview.image.hidden = true;
        preview.imageFallback.hidden = false;
    });

    addMilestoneButton.addEventListener('click', () => addMilestoneRow());

    milestonesContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-milestone]');
        if (!removeButton) return;
        const row = removeButton.closest('[data-milestone-row]');
        if (!row) return;
        removeMilestoneRow(row);
    });

    ['input', 'change'].forEach((eventName) => {
        form.addEventListener(eventName, renderPreview);
    });

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
.stamp-studio-preview-card {
    border-radius: 14px;
    border: 1px solid var(--studio-border);
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.stamp-studio-banner {
    margin: 0 0 12px;
    padding: 20px 22px;
    border: 1px solid #d6e2ff;
    border-radius: 12px;
    background:
        radial-gradient(circle at 90% 12%, rgba(29, 78, 216, 0.16) 0%, rgba(29, 78, 216, 0) 50%),
        linear-gradient(180deg, #f7faff 0%, #eef4ff 100%);
}

.stamp-studio-kicker {
    margin: 0 0 8px;
    color: #335aa6;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.stamp-studio-title {
    margin: 0 0 6px;
    color: var(--studio-ink);
    font-size: 24px;
    font-weight: 700;
    line-height: 1.35;
}

.stamp-studio-desc {
    margin: 0;
    color: var(--studio-muted);
    font-size: 13px;
    line-height: 1.7;
}

.stamp-form-section {
    padding: 18px;
    border: 1px solid var(--studio-border);
    border-radius: 12px;
    background: #fff;
}

.stamp-form-section + .stamp-form-section {
    margin-top: 14px;
}

.stamp-form-section-head h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: var(--studio-ink);
}

.stamp-form-section-head-with-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.stamp-form-grid {
    margin-top: 14px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px 16px;
}

.stamp-form-col-span-2 {
    grid-column: 1 / -1;
}

.stamp-form-check-wrap {
    display: flex;
    align-items: center;
    min-height: 66px;
    padding: 0 12px;
    border: 1px dashed #cad5e2;
    border-radius: 10px;
    background: #fbfdff;
}

.stamp-form-note-group {
    margin-top: 12px;
}

.stamp-form-actions {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--studio-border);
}

.stamp-studio .form-label {
    color: #111827;
    font-size: 13px;
    font-weight: 600;
}

.stamp-studio .form-control {
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #fff;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
}

.stamp-studio .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.stamp-studio .form-text {
    margin-top: 6px;
    font-size: 12px;
}

.btn-outline-primary {
    border: 1px solid #b6ccff;
    background: #fff;
    color: #1d4ed8;
}

.btn-outline-primary:hover {
    background: #eff6ff;
    border-color: #7ea6ff;
}

.btn-outline-danger {
    border: 1px solid #fecaca;
    background: #fff;
    color: #b91c1c;
}

.btn-outline-danger:hover {
    background: #fef2f2;
    border-color: #fda4af;
}

.stamp-milestones {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stamp-milestone-row {
    display: grid;
    grid-template-columns: 132px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: end;
    padding: 12px;
    border: 1px solid #d7e0ea;
    border-radius: 11px;
    background: var(--studio-bg);
}

.stamp-milestone-action-wrap {
    display: flex;
    align-items: flex-end;
}

.stamp-preview-card-header {
    margin-bottom: 8px;
}

.stamp-preview-sub {
    margin: 0 0 12px;
    color: var(--studio-muted);
    font-size: 12px;
}

.stamp-studio-preview-card {
    position: sticky;
    top: 90px;
    align-self: start;
}

.stamp-preview-device {
    padding: 9px;
    border-radius: 30px;
    background: linear-gradient(170deg, #0b1222 0%, #1c2740 100%);
    box-shadow: 0 20px 34px rgba(15, 23, 42, 0.28);
}

.stamp-preview-notch {
    width: 37%;
    height: 18px;
    margin: 2px auto 9px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.38);
}

.stamp-preview-screen {
    border-radius: 21px;
    min-height: 610px;
    padding: 16px 12px;
    background: linear-gradient(180deg, #f8fafc 0%, #edf2f7 100%);
}

.stamp-preview-screen-title {
    margin: 0 0 10px;
    color: #475569;
    font-size: 12px;
    font-weight: 600;
}

.stamp-preview-item {
    border: 1px solid #d6e0ea;
    border-radius: 18px;
    padding: 13px;
    background: #fff;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
}

.stamp-preview-image-wrap {
    margin-bottom: 12px;
    border-radius: 12px;
    overflow: hidden;
    min-height: 128px;
    background: #e2e8f0;
}

.stamp-preview-image {
    display: block;
    width: 100%;
    height: 128px;
    object-fit: cover;
}

.stamp-preview-image-fallback {
    min-height: 128px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1d4ed8;
    font-size: 2rem;
}

.stamp-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.stamp-preview-name {
    margin: 0;
    color: var(--studio-ink);
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
}

.stamp-preview-state {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 999px;
    border: 1px solid #a7f3d0;
    color: var(--studio-success);
    background: #ecfdf5;
}

.stamp-preview-state.is-inactive {
    border-color: #fecaca;
    color: var(--studio-danger);
    background: #fef2f2;
}

.stamp-preview-layout {
    margin: 8px 0 11px;
    color: #4b5563;
    font-size: 12px;
    font-weight: 600;
}

.stamp-preview-slots {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}

.stamp-preview-slot {
    width: 34px;
    height: 34px;
    position: relative;
    border-radius: 50%;
    border: 2px solid #d6e0ea;
    background: #f8fafc;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}

.stamp-preview-slot.is-filled {
    color: #fff;
    border-color: #1d4ed8;
    background: linear-gradient(140deg, #2563eb 0%, #1d4ed8 100%);
}

.stamp-preview-slot.is-milestone {
    box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.14);
}

.stamp-preview-slot-gift {
    position: absolute;
    top: -6px;
    right: -4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 1px solid #fff;
    background: #f97316;
    color: #fff;
    font-size: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.stamp-preview-slots.is-grid {
    max-width: 264px;
    margin: 0 auto;
}

.stamp-preview-slots.is-grid .stamp-preview-slot {
    width: 38px;
    height: 38px;
    font-size: 13px;
}

.stamp-preview-slots.is-row {
    flex-wrap: nowrap;
    justify-content: flex-start;
    overflow-x: auto;
    padding-bottom: 4px;
}

.stamp-preview-slots.is-row .stamp-preview-slot {
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    font-size: 10px;
}

.stamp-preview-slots.is-circle {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    max-width: 196px;
    margin: 0 auto;
}

.stamp-preview-slots.is-circle .stamp-preview-slot {
    width: 33px;
    height: 33px;
    margin: 0 auto;
}

.stamp-preview-item.is-layout-minimal .stamp-preview-slots {
    gap: 4px;
}

.stamp-preview-item.is-layout-minimal .stamp-preview-slot {
    width: 22px;
    height: 22px;
    font-size: 9px;
}

.stamp-preview-item.is-layout-card .stamp-preview-slots {
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #d6e0ea;
    background: #f8fafc;
}

.stamp-preview-overflow {
    margin: 8px 0 0;
    font-size: 12px;
    color: #6b7280;
}

.stamp-preview-progress {
    margin: 12px 0 8px;
    height: 9px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.stamp-preview-progress-bar {
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
}

.stamp-preview-meta {
    margin-bottom: 10px;
}

.stamp-preview-count {
    margin: 0 0 2px;
    color: #374151;
    font-size: 13px;
}

.stamp-preview-validity {
    margin: 0;
    color: #1d4ed8;
    font-size: 12px;
}

.stamp-preview-milestones-wrap {
    margin-bottom: 10px;
}

.stamp-preview-milestones-title {
    margin: 0 0 7px;
    color: #111827;
    font-size: 12px;
    font-weight: 700;
}

.stamp-preview-milestones {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stamp-preview-milestone {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border: 1px solid #d6e0ea;
    border-radius: 9px;
    background: #f8fafc;
    font-size: 12px;
}

.stamp-preview-milestone.is-achieved {
    border-color: #a7f3d0;
    background: #ecfdf5;
}

.stamp-preview-milestone-count {
    min-width: 46px;
    padding: 2px 8px;
    border-radius: 999px;
    text-align: center;
    font-weight: 700;
    color: #111827;
    background: #e2e8f0;
}

.stamp-preview-milestone.is-achieved .stamp-preview-milestone-count {
    color: #fff;
    background: #059669;
}

.stamp-preview-milestone-text {
    color: #334155;
    line-height: 1.4;
}

.stamp-preview-reward-note {
    margin: 0;
    color: #6b7280;
    font-size: 12px;
    line-height: 1.55;
}

@media (max-width: 1280px) {
    .stamp-studio {
        grid-template-columns: 1fr;
    }

    .stamp-studio-preview-card {
        position: static;
    }

    .stamp-preview-screen {
        min-height: 0;
    }
}

@media (max-width: 780px) {
    .stamp-form-grid {
        grid-template-columns: 1fr;
    }

    .stamp-form-col-span-2 {
        grid-column: auto;
    }

    .stamp-milestone-row {
        grid-template-columns: 1fr;
    }

    .stamp-milestone-action-wrap {
        justify-content: flex-end;
    }
}
</style>
@endpush
