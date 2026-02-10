@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.basic') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>LIFFデザイン</span>
</nav>
<link rel="stylesheet" href="{{ asset('css/frontend/style.css') }}">

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb"></i> サンプル</h3>
    </div>
    <div class="card-body" style="display:flex; gap:10px; flex-wrap:wrap;">
        @foreach($templates as $template)
            <button type="button" class="btn btn-secondary btn-sm" data-template-id="{{ $template['id'] }}">{{ $template['name'] }}</button>
        @endforeach
        <div class="form-text text-muted" style="width:100%; margin-top:6px;">現在選択中のタブにサンプルが適用されます。</div>
    </div>
</div>

<form action="{{ admin_route('admin.settings.liff-designer.update') }}" method="POST" id="liffDesignerForm">
    @csrf
    <input type="hidden" name="layouts_json" id="layoutsJson" value="">

    <div class="liff-designer">
        <aside class="liff-designer__sidebar">
            <div class="liff-designer__panel">
                <div class="liff-designer__panel-title">タブ</div>
                <div id="tabList" class="liff-designer__tablist"></div>
            </div>
            <div class="liff-designer__panel">
                <div class="liff-designer__panel-title">ブロック追加</div>
                <select id="blockTypeSelect" class="form-control"></select>
                <button type="button" class="btn btn-secondary btn-sm" id="addBlockBtn" style="margin-top:10px; width:100%;"><i class="fas fa-plus"></i> 追加</button>
            </div>
        </aside>

        <section class="liff-designer__editor">
            <div class="liff-designer__panel">
                <div class="liff-designer__panel-title">ブロック一覧</div>
                <div id="blockList" class="liff-designer__blocklist"></div>
            </div>
            <div class="liff-designer__panel">
                <div class="liff-designer__panel-title">プロパティ</div>
                <div id="blockEditor" class="liff-designer__editor-body"></div>
            </div>
        </section>

        <section class="liff-designer__preview">
            <div class="liff-designer__panel">
                <div class="liff-designer__panel-title">プレビュー</div>
                <div id="previewArea" class="liff-designer__preview-body"></div>
            </div>
        </section>
    </div>

    <div class="form-actions" style="margin-top:20px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
    </div>
</form>

<template id="formFieldRowTemplate">
    <div class="form-field-row">
        <input type="text" class="form-control field-label" placeholder="ラベル">
        <select class="form-control field-type">
            <option value="text">テキスト</option>
            <option value="email">メール</option>
            <option value="tel">電話</option>
            <option value="textarea">テキストエリア</option>
        </select>
        <input type="text" class="form-control field-placeholder" placeholder="プレースホルダー">
        <label class="field-required"><input type="checkbox"> 必須</label>
        <button type="button" class="btn btn-danger btn-sm remove-form-field"><i class="fas fa-times"></i></button>
    </div>
</template>

<style>
.liff-designer {
    display: grid;
    grid-template-columns: 200px minmax(360px, 1fr) minmax(320px, 1fr);
    gap: 16px;
}
.liff-designer__panel {
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e6e8ee;
    padding: 14px;
}
.liff-designer__panel-title {
    font-weight: 700;
    margin-bottom: 12px;
}
.liff-designer__sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.liff-designer__tablist {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.liff-tab-btn {
    border: 1px solid #e1e4ea;
    border-radius: 8px;
    background: #f6f7fb;
    padding: 8px 10px;
    text-align: left;
    font-weight: 600;
    cursor: pointer;
}
.liff-tab-btn.active {
    background: #1d4ed8;
    color: #fff;
    border-color: #1d4ed8;
}
.liff-designer__editor {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.liff-designer__blocklist {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 60px;
}
.block-card {
    border: 1px solid #e1e4ea;
    border-radius: 8px;
    background: #fafbff;
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}
.block-card.active {
    border-color: #1d4ed8;
    background: #eef2ff;
}
.block-card__meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.block-card__actions {
    display: flex;
    gap: 6px;
}
.block-card__type {
    font-size: 12px;
    color: #6b7280;
}
.liff-designer__editor-body .form-group {
    margin-bottom: 12px;
}
.liff-designer__preview-body {
    background: #f5f6fb;
    border-radius: 12px;
    padding: 16px;
    min-height: 480px;
}
.preview-block {
    background: #fff;
    border-radius: 10px;
    padding: 14px;
    border: 1px solid #e6e8ee;
    margin-bottom: 12px;
    position: relative;
}
.preview-block.dragging {
    opacity: 0.6;
}
.preview-block__handle {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 12px;
    color: #9ca3af;
}
.preview-editable {
    outline: none;
    border-radius: 6px;
    padding: 2px 4px;
}
.preview-editable:focus {
    background: #f0f4ff;
}
.preview-image {
    width: 100%;
    border-radius: 8px;
    margin-top: 8px;
}
.preview-image-input {
    width: 100%;
    margin-top: 8px;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    font-size: 12px;
}
.preview-title {
    font-weight: 700;
    margin-bottom: 6px;
}
.preview-list {
    margin: 8px 0 0 18px;
}
.preview-chip {
    display: inline-block;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4338ca;
    margin-right: 6px;
}
.preview-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 10px;
}
.preview-card {
    border-radius: 8px;
    background: #f8fafc;
    padding: 10px;
    border: 1px dashed #e2e8f0;
    font-size: 12px;
}
.form-field-row {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr 1.4fr auto auto;
    gap: 8px;
    align-items: center;
    margin-bottom: 8px;
}
.form-field-row .field-required {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}
@media (max-width: 1200px) {
    .liff-designer {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const TAB_LABELS = {
    home: 'ホーム',
    products: '商品',
    orders: '注文',
    stamps: 'スタンプ',
    coupons: 'クーポン',
    queue: '順番待ち',
    reservations: '予約',
    member: '会員',
    gallery: 'ギャラリー',
    announcements: 'お知らせ',
};

const BLOCK_TYPES = [
    { value: 'label', label: 'ラベル' },
    { value: 'text', label: 'テキスト' },
    { value: 'hero', label: 'ヒーロー' },
    { value: 'image', label: '画像' },
    { value: 'button', label: 'ボタン' },
    { value: 'list', label: 'リスト' },
    { value: 'slider', label: 'スライダー' },
    { value: 'form', label: 'フォーム' },
    { value: 'collection', label: 'コレクション' },
    { value: 'table', label: 'テーブル' },
    { value: 'data', label: 'データ表示' },
    { value: 'gallery', label: 'ギャラリー' },
    { value: 'spacer', label: 'スペーサー' },
];

const DATA_SOURCES = [
    { value: 'products', label: '商品' },
    { value: 'orders', label: '注文' },
    { value: 'reservations', label: '予約' },
    { value: 'member', label: '会員' },
    { value: 'stamps', label: 'スタンプ' },
    { value: 'coupons', label: 'クーポン' },
    { value: 'queue', label: '順番待ち' },
    { value: 'gallery', label: 'ギャラリー' },
    { value: 'announcements', label: 'お知らせ' },
];

const templates = @json($templates);
const initialLayouts = @json($layouts);

const state = {
    layouts: JSON.parse(JSON.stringify(initialLayouts || {})),
    activeTab: Object.keys(initialLayouts || {})[0] || 'home',
    activeIndex: 0,
};

const tabList = document.getElementById('tabList');
const blockList = document.getElementById('blockList');
const blockEditor = document.getElementById('blockEditor');
const previewArea = document.getElementById('previewArea');
const blockTypeSelect = document.getElementById('blockTypeSelect');
const layoutsJson = document.getElementById('layoutsJson');

function ensureTabLayouts() {
    Object.keys(TAB_LABELS).forEach((key) => {
        if (!state.layouts[key]) state.layouts[key] = [];
    });
}

function renderTabs() {
    ensureTabLayouts();
    tabList.innerHTML = '';
    Object.keys(TAB_LABELS).forEach((key) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'liff-tab-btn' + (state.activeTab === key ? ' active' : '');
        btn.textContent = TAB_LABELS[key];
        btn.addEventListener('click', () => {
            state.activeTab = key;
            state.activeIndex = 0;
            renderTabs();
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        tabList.appendChild(btn);
    });
}

function renderBlockTypeSelect() {
    blockTypeSelect.innerHTML = '';
    BLOCK_TYPES.forEach((type) => {
        const opt = document.createElement('option');
        opt.value = type.value;
        opt.textContent = type.label;
        blockTypeSelect.appendChild(opt);
    });
}

function getBlocks() {
    return state.layouts[state.activeTab] || [];
}

function createDefaultBlock(type) {
    if (type === 'label') return { type: 'label', title: 'ラベル' };
    if (type === 'hero') return { type: 'hero', title: 'タイトル', body: '説明文', image_url: '', button_label: 'ボタン', button_url: '#products' };
    if (type === 'image') return { type: 'image', title: '画像', body: '', image_url: '' };
    if (type === 'button') return { type: 'button', title: 'ボタン', body: '', button_label: 'クリック', button_url: '#home' };
    if (type === 'list') return { type: 'list', title: 'リスト', list_items: ['項目1', '項目2', '項目3'] };
    if (type === 'slider') return { type: 'slider', title: 'スライダー', slider_images: [] };
    if (type === 'form') return { type: 'form', title: 'フォーム', form_fields: [{ label: 'お名前', type: 'text', placeholder: '例: 山田 太郎', required: true }], form_button_label: '送信' };
    if (type === 'collection') return { type: 'collection', source: 'products', title: 'コレクション' };
    if (type === 'table') return { type: 'table', source: 'orders', title: 'テーブル' };
    if (type === 'data') return { type: 'data', source: 'products', title: 'データ表示' };
    if (type === 'gallery') return { type: 'gallery', source: 'gallery', title: 'ギャラリー' };
    if (type === 'spacer') return { type: 'spacer', height: 24 };
    return { type: 'text', title: 'テキスト', body: '説明文' };
}

function renderBlockList() {
    blockList.innerHTML = '';
    const blocks = getBlocks();
    if (blocks.length === 0) {
        blockList.innerHTML = '<div class="text-muted">ブロックがありません</div>';
        return;
    }
    blocks.forEach((block, idx) => {
        const card = document.createElement('div');
        card.className = 'block-card' + (state.activeIndex === idx ? ' active' : '');
        card.draggable = true;
        card.dataset.index = idx;
        card.innerHTML = `
            <div class="block-card__meta">
                <strong>${escapeHtml(block.title || TAB_LABELS[block.type] || block.type || 'ブロック')}</strong>
                <span class="block-card__type">${escapeHtml(block.type || 'text')}</span>
            </div>
            <div class="block-card__actions">
                <button type="button" class="btn btn-danger btn-sm" data-action="remove">削除</button>
            </div>
        `;
        card.addEventListener('click', (e) => {
            if (e.target && e.target.dataset.action === 'remove') return;
            state.activeIndex = idx;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.querySelector('[data-action="remove"]').addEventListener('click', (e) => {
            e.stopPropagation();
            blocks.splice(idx, 1);
            state.activeIndex = Math.max(0, idx - 1);
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', idx.toString());
        });
        card.addEventListener('dragover', (e) => e.preventDefault());
        card.addEventListener('drop', (e) => {
            e.preventDefault();
            const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
            const to = idx;
            if (Number.isNaN(from) || from === to) return;
            const moved = blocks.splice(from, 1)[0];
            blocks.splice(to, 0, moved);
            state.activeIndex = to;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        blockList.appendChild(card);
    });
}

function renderEditor() {
    const blocks = getBlocks();
    const block = blocks[state.activeIndex];
    if (!block) {
        blockEditor.innerHTML = '<div class="text-muted">ブロックを選択してください。</div>';
        return;
    }
    const typeOptions = BLOCK_TYPES.map((t) => `<option value="${t.value}" ${block.type === t.value ? 'selected' : ''}>${t.label}</option>`).join('');
    const sourceOptions = DATA_SOURCES.map((s) => `<option value="${s.value}" ${block.source === s.value ? 'selected' : ''}>${s.label}</option>`).join('');

    blockEditor.innerHTML = `
        <div class="form-group">
            <label class="form-label">タイプ</label>
            <select class="form-control" id="editType">${typeOptions}</select>
        </div>
        <div class="form-group">
            <label class="form-label">タイトル</label>
            <input type="text" class="form-control" id="editTitle" value="${escapeHtml(block.title || '')}">
        </div>
        <div class="form-group">
            <label class="form-label">本文</label>
            <textarea class="form-control" rows="3" id="editBody">${escapeHtml(block.body || '')}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">画像URL</label>
            <input type="text" class="form-control" id="editImage" value="${escapeHtml(block.image_url || '')}">
        </div>
        <div class="form-group">
            <label class="form-label">ボタン</label>
            <div style="display:flex; gap:8px;">
                <input type="text" class="form-control" id="editButtonLabel" placeholder="ラベル" value="${escapeHtml(block.button_label || '')}">
                <input type="text" class="form-control" id="editButtonUrl" placeholder="#tab or URL" value="${escapeHtml(block.button_url || '')}">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">リスト（改行区切り）</label>
            <textarea class="form-control" rows="3" id="editList">${escapeHtml(Array.isArray(block.list_items) ? block.list_items.join('\n') : (block.list_items || ''))}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">スライダー画像URL（改行区切り）</label>
            <textarea class="form-control" rows="3" id="editSlider">${escapeHtml(Array.isArray(block.slider_images) ? block.slider_images.join('\n') : (block.slider_images || ''))}</textarea>
        </div>
        <div class="form-group">
            <label class="form-label">データソース</label>
            <select class="form-control" id="editSource">${sourceOptions}</select>
        </div>
        <div class="form-group">
            <label class="form-label">余白（px）</label>
            <div style="display:flex; gap:8px;">
                <input type="number" class="form-control" id="editPaddingY" min="0" max="80" value="${block.padding_y || 0}" placeholder="上下">
                <input type="number" class="form-control" id="editPaddingX" min="0" max="80" value="${block.padding_x || 0}" placeholder="左右">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">整列</label>
            <select class="form-control" id="editAlign">
                <option value="left" ${block.align === 'left' ? 'selected' : ''}>左</option>
                <option value="center" ${block.align === 'center' ? 'selected' : ''}>中央</option>
                <option value="right" ${block.align === 'right' ? 'selected' : ''}>右</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">背景色</label>
            <input type="color" class="form-control" id="editBg" value="${escapeHtml(block.background_color || '#ffffff')}">
        </div>
        <div class="form-group">
            <label class="form-label">文字色</label>
            <input type="color" class="form-control" id="editTextColor" value="${escapeHtml(block.text_color || '#111111')}">
        </div>
        <div class="form-group">
            <label class="form-label">枠線色</label>
            <input type="color" class="form-control" id="editBorderColor" value="${escapeHtml(block.border_color || '#e6e8ee')}">
        </div>
        <div class="form-group">
            <label class="form-label">枠線幅</label>
            <input type="range" class="form-control" id="editBorderWidth" min="0" max="8" value="${block.border_width || 1}">
        </div>
        <div class="form-group">
            <label class="form-label">角丸</label>
            <input type="range" class="form-control" id="editRadius" min="0" max="30" value="${block.border_radius || 12}">
        </div>
        <div class="form-group">
            <label class="form-label">スペーサー高さ</label>
            <input type="number" class="form-control" id="editHeight" min="4" max="200" value="${block.height || 24}">
        </div>
        <div class="form-group">
            <label class="form-label">フォーム項目</label>
            <div id="formFields"></div>
            <button type="button" class="btn btn-secondary btn-sm" id="addFormFieldBtn"><i class="fas fa-plus"></i> 項目追加</button>
            <div style="margin-top:8px;">
                <input type="text" class="form-control" id="editFormButton" placeholder="送信ボタンラベル" value="${escapeHtml(block.form_button_label || '')}">
            </div>
        </div>
    `;

    const typeEl = blockEditor.querySelector('#editType');
    const titleEl = blockEditor.querySelector('#editTitle');
    const bodyEl = blockEditor.querySelector('#editBody');
    const imageEl = blockEditor.querySelector('#editImage');
    const buttonLabelEl = blockEditor.querySelector('#editButtonLabel');
    const buttonUrlEl = blockEditor.querySelector('#editButtonUrl');
    const listEl = blockEditor.querySelector('#editList');
    const sliderEl = blockEditor.querySelector('#editSlider');
    const sourceEl = blockEditor.querySelector('#editSource');
    const paddingYEl = blockEditor.querySelector('#editPaddingY');
    const paddingXEl = blockEditor.querySelector('#editPaddingX');
    const alignEl = blockEditor.querySelector('#editAlign');
    const bgEl = blockEditor.querySelector('#editBg');
    const textColorEl = blockEditor.querySelector('#editTextColor');
    const borderColorEl = blockEditor.querySelector('#editBorderColor');
    const borderWidthEl = blockEditor.querySelector('#editBorderWidth');
    const radiusEl = blockEditor.querySelector('#editRadius');
    const heightEl = blockEditor.querySelector('#editHeight');
    const formFields = blockEditor.querySelector('#formFields');
    const addFormFieldBtn = blockEditor.querySelector('#addFormFieldBtn');
    const formButtonEl = blockEditor.querySelector('#editFormButton');

    const update = () => {
        block.type = typeEl.value;
        block.title = titleEl.value;
        block.body = bodyEl.value;
        block.image_url = imageEl.value;
        block.button_label = buttonLabelEl.value;
        block.button_url = buttonUrlEl.value;
        block.list_items = listEl.value ? listEl.value.split(/\n/).map((v) => v.trim()).filter(Boolean) : [];
        block.slider_images = sliderEl.value ? sliderEl.value.split(/\n/).map((v) => v.trim()).filter(Boolean) : [];
        block.source = sourceEl.value;
        block.padding_y = parseInt(paddingYEl.value || '0', 10) || 0;
        block.padding_x = parseInt(paddingXEl.value || '0', 10) || 0;
        block.align = alignEl.value;
        block.background_color = bgEl.value;
        block.text_color = textColorEl.value;
        block.border_color = borderColorEl.value;
        block.border_width = parseInt(borderWidthEl.value || '1', 10) || 0;
        block.border_radius = parseInt(radiusEl.value || '12', 10) || 0;
        block.height = parseInt(heightEl.value || '24', 10);
        block.form_button_label = formButtonEl.value;
        renderBlockList();
        renderPreview();
    };

    [typeEl, titleEl, bodyEl, imageEl, buttonLabelEl, buttonUrlEl, listEl, sliderEl, sourceEl, paddingYEl, paddingXEl, alignEl, bgEl, textColorEl, borderColorEl, borderWidthEl, radiusEl, heightEl, formButtonEl]
        .forEach((el) => el.addEventListener('input', update));

    const buildFormFieldRow = (field = {}) => {
        const tpl = document.getElementById('formFieldRowTemplate');
        const row = tpl.content.firstElementChild.cloneNode(true);
        row.querySelector('.field-label').value = field.label || '';
        row.querySelector('.field-type').value = field.type || 'text';
        row.querySelector('.field-placeholder').value = field.placeholder || '';
        row.querySelector('.field-required input').checked = !!field.required;
        row.querySelector('.remove-form-field').addEventListener('click', () => {
            row.remove();
            saveFormFields();
        });
        row.querySelectorAll('input, select').forEach((input) => {
            input.addEventListener('input', saveFormFields);
            input.addEventListener('change', saveFormFields);
        });
        return row;
    };

    const saveFormFields = () => {
        const fields = [];
        formFields.querySelectorAll('.form-field-row').forEach((row) => {
            fields.push({
                label: row.querySelector('.field-label').value,
                type: row.querySelector('.field-type').value,
                placeholder: row.querySelector('.field-placeholder').value,
                required: row.querySelector('.field-required input').checked,
            });
        });
        block.form_fields = fields;
        renderPreview();
    };

    formFields.innerHTML = '';
    (block.form_fields || []).forEach((field) => formFields.appendChild(buildFormFieldRow(field)));
    addFormFieldBtn.addEventListener('click', () => {
        formFields.appendChild(buildFormFieldRow({}));
        saveFormFields();
    });

    updateEditorVisibility(block.type);
    typeEl.addEventListener('change', () => {
        update();
        updateEditorVisibility(typeEl.value);
    });
}

function updateEditorVisibility(type) {
    const show = (selector, visible) => {
        const el = blockEditor.querySelector(selector);
        if (!el) return;
        const wrapper = el.closest('.form-group') || el;
        wrapper.style.display = visible ? '' : 'none';
    };
    const isTextLike = ['text', 'hero', 'image', 'button', 'list', 'label'].includes(type);
    show('#editTitle', type !== 'spacer');
    show('#editBody', ['text', 'hero', 'image', 'button', 'list'].includes(type));
    show('#editImage', ['hero', 'image'].includes(type));
    show('#editButtonLabel', ['hero', 'button'].includes(type));
    show('#editButtonUrl', ['hero', 'button'].includes(type));
    show('#editList', type === 'list');
    show('#editSlider', type === 'slider');
    show('#editSource', ['data', 'collection', 'table', 'gallery'].includes(type));
    show('#editHeight', type === 'spacer');
    show('#formFields', type === 'form');
    show('#addFormFieldBtn', type === 'form');
    show('#editFormButton', type === 'form');
    show('#editPaddingY', type !== 'spacer');
    show('#editPaddingX', type !== 'spacer');
    show('#editAlign', isTextLike || ['data', 'collection', 'table', 'gallery', 'form'].includes(type));
    show('#editBg', isTextLike || ['data', 'collection', 'table', 'gallery', 'form'].includes(type));
    show('#editTextColor', isTextLike || ['data', 'collection', 'table', 'gallery', 'form'].includes(type));
    show('#editBorderColor', type !== 'spacer');
    show('#editBorderWidth', type !== 'spacer');
    show('#editRadius', type !== 'spacer');
}

function renderPreview() {
    const blocks = getBlocks();
    if (!blocks.length) {
        previewArea.innerHTML = '<div class="text-muted">ブロックを追加してください。</div>';
        return;
    }
    previewArea.innerHTML = `<div class="builder-container">${blocks.map((block, idx) => renderPreviewBlock(block, idx)).join('')}</div>`;
    bindPreviewInteractions();
}

function renderPreviewBlock(block, idx) {
    const title = `<h3 class="builder-title preview-editable" contenteditable="true" data-field="title">${escapeHtml(block.title || '')}</h3>`;
    const body = `<p class="builder-body preview-editable" contenteditable="true" data-field="body">${escapeHtml(block.body || '')}</p>`;
    if (block.type === 'spacer') {
        return `<div style="height:${block.height || 24}px;"></div>`;
    }
    if (block.type === 'list') {
        const items = Array.isArray(block.list_items) ? block.list_items : [];
        const list = items.length ? `<ul class="builder-list preview-list">${items.map((i) => `<li>${escapeHtml(i)}</li>`).join('')}</ul>` : '';
        return `<div class="builder-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}${list}</div>`;
    }
    if (block.type === 'form') {
        const fields = Array.isArray(block.form_fields) ? block.form_fields : [];
        const fieldList = fields.map((f) => `<div class="preview-card">${escapeHtml(f.label || '項目')}</div>`).join('');
        return `<div class="builder-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}<div class="preview-grid">${fieldList}</div></div>`;
    }
    if (block.type === 'slider') {
        const images = Array.isArray(block.slider_images) ? block.slider_images : [];
        const slides = images.length
            ? images.map((url) => `<div class="preview-card">${escapeHtml(url)}</div>`).join('')
            : '<div class="preview-card">画像なし</div>';
        return `<div class="builder-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}<div class="preview-grid">${slides}</div></div>`;
    }
    if (['data', 'collection', 'table', 'gallery'].includes(block.type)) {
        const source = block.source || (block.type === 'gallery' ? 'gallery' : '');
        return `<div class="builder-block builder-data-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}<span class="preview-chip">${escapeHtml(source || 'data')}</span><div class="preview-grid"><div class="preview-card">サンプル</div><div class="preview-card">サンプル</div></div></div>`;
    }
    if (block.type === 'hero') {
        const image = block.image_url ? `<img class="preview-image" src="${escapeHtml(block.image_url)}" alt="">` : '<div class="preview-card">ヒーロー画像</div>';
        const input = `<input class="preview-image-input" data-field="image_url" placeholder="画像URL" value="${escapeHtml(block.image_url || '')}">`;
        return `<div class="builder-block builder-hero preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${image}<div class="builder-hero-content">${title}${body}</div>${input}</div>`;
    }
    if (block.type === 'image') {
        const image = block.image_url ? `<img class="preview-image" src="${escapeHtml(block.image_url)}" alt="">` : '<div class="preview-card">画像</div>';
        const input = `<input class="preview-image-input" data-field="image_url" placeholder="画像URL" value="${escapeHtml(block.image_url || '')}">`;
        return `<div class="builder-block builder-image-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${image}${body}${input}</div>`;
    }
    if (block.type === 'button') {
        return `<div class="builder-block builder-button-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}<a class="builder-btn" href="javascript:void(0);">${escapeHtml(block.button_label || 'ボタン')}</a></div>`;
    }
    return `<div class="builder-block preview-block" draggable="true" data-index="${idx}"><span class="preview-block__handle">↕</span>${title}${body}</div>`;
}

function bindPreviewInteractions() {
    previewArea.querySelectorAll('.preview-block').forEach((blockEl) => {
        blockEl.addEventListener('dragstart', (e) => {
            blockEl.classList.add('dragging');
            e.dataTransfer.setData('text/plain', blockEl.dataset.index);
        });
        blockEl.addEventListener('dragend', () => blockEl.classList.remove('dragging'));
        blockEl.addEventListener('dragover', (e) => e.preventDefault());
        blockEl.addEventListener('drop', (e) => {
            e.preventDefault();
            const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
            const to = parseInt(blockEl.dataset.index, 10);
            if (Number.isNaN(from) || Number.isNaN(to) || from === to) return;
            const blocks = getBlocks();
            const moved = blocks.splice(from, 1)[0];
            blocks.splice(to, 0, moved);
            state.activeIndex = to;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
    });

    previewArea.querySelectorAll('.preview-editable').forEach((el) => {
        el.addEventListener('input', () => {
            const parent = el.closest('.preview-block');
            if (!parent) return;
            const idx = parseInt(parent.dataset.index, 10);
            const field = el.dataset.field;
            const blocks = getBlocks();
            if (!blocks[idx] || !field) return;
            blocks[idx][field] = el.textContent.trim();
            renderBlockList();
        });
    });

    previewArea.querySelectorAll('.preview-image-input').forEach((input) => {
        input.addEventListener('input', () => {
            const parent = input.closest('.preview-block');
            if (!parent) return;
            const idx = parseInt(parent.dataset.index, 10);
            const blocks = getBlocks();
            if (!blocks[idx]) return;
            blocks[idx].image_url = input.value;
            renderBlockList();
            renderPreview();
        });
    });
}

function applyTemplate(templateId) {
    const template = templates.find((t) => t.id === templateId);
    if (!template) return;
    state.layouts[state.activeTab] = JSON.parse(JSON.stringify(template.blocks || []));
    state.activeIndex = 0;
    renderBlockList();
    renderEditor();
    renderPreview();
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.querySelectorAll('[data-template-id]').forEach((btn) => {
    btn.addEventListener('click', () => applyTemplate(btn.dataset.templateId));
});

document.getElementById('addBlockBtn').addEventListener('click', () => {
    const blocks = getBlocks();
    blocks.push(createDefaultBlock(blockTypeSelect.value));
    state.activeIndex = blocks.length - 1;
    renderBlockList();
    renderEditor();
    renderPreview();
});

document.getElementById('liffDesignerForm').addEventListener('submit', () => {
    layoutsJson.value = JSON.stringify(state.layouts);
});

renderTabs();
renderBlockTypeSelect();
renderBlockList();
renderEditor();
renderPreview();
</script>
@endsection
