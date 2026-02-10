@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')

@section('content')
<link rel="stylesheet" href="{{ asset('css/frontend/style.css') }}">

<form action="{{ admin_route('admin.settings.liff-designer.update') }}" method="POST" id="liffDesignerForm" class="liff-designer-shell">
    @csrf
    <input type="hidden" name="layouts_json" id="layoutsJson" value="">
    <input type="hidden" name="custom_css" id="customCssInput" value="{{ $customCss ?? '' }}">
    <input type="hidden" name="tab_settings_json" id="tabSettingsJson" value="">
    <div class="liff-designer-stage">
        <header class="liff-designer-topbar">
            <div class="topbar-left">
                <div class="topbar-title">LIFF デザイン</div>
            </div>
            <div class="topbar-right">
                <button type="button" class="btn btn-secondary btn-sm" id="toggleCssBtn">CSS</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
        </header>

        <section class="liff-designer-main">
            <aside class="liff-designer-palette">
                <div class="palette-section">
                    <div class="palette-title">テンプレート</div>
                    <div class="template-list">
                        @foreach($templates as $template)
                            <button type="button" class="btn btn-secondary btn-sm template-btn" data-template-id="{{ $template['id'] }}">{{ $template['name'] }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="palette-section">
                    <div class="palette-title">ブロック追加</div>
                    <div id="paletteBlocks" class="palette-blocks"></div>
                </div>
                <div class="palette-section">
                    <div class="palette-title">ブロック一覧</div>
                    <div id="blockList" class="liff-designer__blocklist"></div>
                </div>
                <div class="palette-section css-panel" id="cssPanel">
                    <div class="palette-title">カスタムCSS</div>
                    <textarea id="customCssEditor" class="form-control" rows="8" placeholder="例: .builder-block { border-radius: 20px; }">{{ $customCss ?? '' }}</textarea>
                </div>
            </aside>

            <div class="panel-resizer" id="leftResizer" aria-hidden="true"></div>

            <div class="liff-designer-canvas">
                <div class="canvas-shell">
                    <div class="canvas-header">キャンバス</div>
                    <div id="previewArea" class="canvas-body drop-area"></div>
                </div>
            </div>

            <div class="panel-resizer" id="rightResizer" aria-hidden="true"></div>

            <aside class="liff-designer-properties">
                <div class="properties-header">画面</div>
                <div id="tabList" class="screen-list"></div>
                <div class="properties-header" style="margin-top:16px;">画面設定</div>
                <div class="screen-settings">
                    <div class="form-group">
                        <label class="form-label">背景色</label>
                        <input type="color" id="screenBgColor" class="form-control" value="#ffffff">
                    </div>
                    <div class="form-group">
                        <label class="form-label">背景画像URL</label>
                        <input type="text" id="screenBgImage" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">背景表示</label>
                        <select id="screenBgSize" class="form-control">
                            <option value="cover">cover</option>
                            <option value="contain">contain</option>
                            <option value="auto">auto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">背景位置</label>
                        <select id="screenBgPos" class="form-control">
                            <option value="center">center</option>
                            <option value="top">top</option>
                            <option value="bottom">bottom</option>
                            <option value="left">left</option>
                            <option value="right">right</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">コンテンツ幅</label>
                        <input type="range" id="screenMaxWidth" class="form-control" min="320" max="720" value="440">
                    </div>
                    <div class="form-group">
                        <label class="form-label">左右余白</label>
                        <input type="range" id="screenPadding" class="form-control" min="8" max="40" value="20">
                    </div>
                </div>
                <div class="properties-header" style="margin-top:16px;">プロパティ</div>
                <div id="blockEditor" class="liff-designer__editor-body"></div>
            </aside>
        </section>
    </div>
</form>

<div class="property-drawer" id="propertyDrawer">
    <div class="drawer-header">
        <div class="drawer-title">プロパティ</div>
        <button type="button" class="btn btn-secondary btn-sm" id="closeDrawerBtn">閉じる</button>
    </div>
    <div id="blockEditor" class="liff-designer__editor-body"></div>
</div>

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
.admin-container .sidebar,
.admin-container .content-header {
    display: none;
}
.admin-container .content {
    padding: 0;
}
.liff-designer-shell {
    min-height: 100vh;
    background: #e6e9f0;
}
.liff-designer-stage {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.liff-designer-topbar {
    height: 64px;
    background: #0f172a;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    gap: 16px;
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.2);
}
.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}
.topbar-title {
    font-weight: 700;
    font-size: 16px;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.liff-designer-shell .btn {
    border-radius: 8px;
    border: 1px solid transparent;
    font-size: 12px;
    padding: 6px 10px;
}
.liff-designer-shell .btn.btn-primary {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.liff-designer-shell .btn.btn-secondary {
    background: #f1f5f9;
    color: #1f2937;
    border-color: #e2e8f0;
}
.liff-designer-shell .btn.btn-danger {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
}
.liff-designer-main {
    display: grid;
    grid-template-columns: var(--left-width, 240px) 8px minmax(520px, 1fr) 8px var(--right-width, 320px);
    gap: 16px;
    padding: 16px;
    flex: 1;
}
.liff-designer-palette,
.liff-designer-properties {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #d0d7e2;
    padding: 14px;
    height: calc(100vh - 96px);
    overflow: auto;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}
.css-panel {
    display: none;
}
.css-panel.is-open {
    display: block;
}
.palette-section + .palette-section {
    margin-top: 16px;
}
.palette-title,
.properties-header {
    font-weight: 700;
    margin-bottom: 12px;
}
.template-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.palette-blocks {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.palette-item {
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 8px;
    font-size: 12px;
    background: #f1f5f9;
    cursor: grab;
    text-align: center;
    user-select: none;
}
.palette-item:active {
    cursor: grabbing;
}
.liff-tab-btn {
    border: 1px solid #253047;
    border-radius: 999px;
    background: #1f2937;
    padding: 6px 12px;
    color: #cbd5f5;
    font-weight: 600;
    cursor: pointer;
    font-size: 12px;
}
.liff-tab-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
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
.block-card.drag-over {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
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
.liff-designer-canvas {
    display: flex;
    align-items: center;
    justify-content: center;
}
.panel-resizer {
    width: 8px;
    cursor: col-resize;
    background: transparent;
    border-radius: 999px;
}
.panel-resizer:hover {
    background: rgba(37, 99, 235, 0.2);
}
.canvas-shell {
    width: min(440px, 100%);
    background: #fff;
    border-radius: 18px;
    border: 1px solid #cbd5e1;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
    display: flex;
    flex-direction: column;
    height: calc(100vh - 120px);
}
.canvas-header {
    padding: 10px 14px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 12px;
    color: #6b7280;
    text-align: center;
}
.canvas-body {
    padding: 12px;
    overflow: auto;
    background: #f1f5f9;
    border-radius: 0 0 18px 18px;
    height: 100%;
}
.drop-area.is-over {
    outline: 2px dashed #2563eb;
    outline-offset: -6px;
    background: #e0e7ff;
}
.drop-hint {
    text-align: center;
    color: #475569;
    font-size: 12px;
    padding: 16px 10px;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
}
.drop-area {
    min-height: 100%;
}
.drop-indicator {
    height: 6px;
    background: #2563eb;
    border-radius: 999px;
    margin: 6px 0;
}
.screen-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.screen-settings .form-group {
    margin-bottom: 10px;
}
.screen-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 10px;
    background: #f9fafb;
    cursor: pointer;
}
.screen-card.active {
    border-color: #2563eb;
    background: #eef2ff;
}
.screen-label {
    font-weight: 600;
    font-size: 12px;
    margin-bottom: 6px;
}
.screen-thumb {
    height: 80px;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
    background: #fff;
    font-size: 10px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
}
.property-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: 360px;
    height: 100vh;
    background: #ffffff;
    border-left: 1px solid #e5e7eb;
    box-shadow: -8px 0 24px rgba(15, 23, 42, 0.12);
    transform: translateX(100%);
    transition: transform 0.2s ease;
    z-index: 50;
    display: flex;
    flex-direction: column;
}
.property-drawer.is-open {
    transform: translateX(0);
}
.drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px;
    border-bottom: 1px solid #e5e7eb;
}
.drawer-title {
    font-weight: 700;
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
    .liff-designer-main {
        grid-template-columns: 1fr;
    }
    .liff-designer-palette,
    .liff-designer-properties {
        height: auto;
    }
    .canvas-shell {
        height: auto;
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

const initialLayouts = @json($layouts);
const templates = @json($templates);
const initialTabSettings = @json($tabSettings ?? []);

const state = {
    layouts: JSON.parse(JSON.stringify(initialLayouts || {})),
    activeTab: Object.keys(initialLayouts || {})[0] || 'home',
    activeIndex: 0,
    tabSettings: JSON.parse(JSON.stringify(initialTabSettings || {})),
};

const tabList = document.getElementById('tabList');
const blockList = document.getElementById('blockList');
const blockEditor = document.getElementById('blockEditor');
const previewArea = document.getElementById('previewArea');
const paletteBlocks = document.getElementById('paletteBlocks');
const layoutsJson = document.getElementById('layoutsJson');
const tabSettingsJson = document.getElementById('tabSettingsJson');
const customCssInput = document.getElementById('customCssInput');
const customCssEditor = document.getElementById('customCssEditor');
const toggleCssBtn = document.getElementById('toggleCssBtn');
const cssPanel = document.getElementById('cssPanel');
const screenBgColor = document.getElementById('screenBgColor');
const screenBgImage = document.getElementById('screenBgImage');
const screenBgSize = document.getElementById('screenBgSize');
const screenBgPos = document.getElementById('screenBgPos');
const screenMaxWidth = document.getElementById('screenMaxWidth');
const screenPadding = document.getElementById('screenPadding');
const leftResizer = document.getElementById('leftResizer');
const rightResizer = document.getElementById('rightResizer');
const designerMain = document.querySelector('.liff-designer-main');
const propertyDrawer = document.getElementById('propertyDrawer');
const closeDrawerBtn = document.getElementById('closeDrawerBtn');

function ensureTabLayouts() {
    Object.keys(TAB_LABELS).forEach((key) => {
        if (!state.layouts[key]) state.layouts[key] = [];
        if (!state.tabSettings[key]) state.tabSettings[key] = {};
    });
}

function renderTabs() {
    ensureTabLayouts();
    tabList.innerHTML = '';
    Object.keys(TAB_LABELS).forEach((key) => {
        const card = document.createElement('div');
        card.className = 'screen-card' + (state.activeTab === key ? ' active' : '');
        card.innerHTML = `
            <div class="screen-label">${TAB_LABELS[key]}</div>
            <div class="screen-thumb">${renderScreenThumb(key)}</div>
        `;
        card.addEventListener('click', () => {
            state.activeTab = key;
            state.activeIndex = 0;
            renderTabs();
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        tabList.appendChild(card);
    });
}

function renderScreenThumb(tab) {
    const blocks = state.layouts[tab] || [];
    const labels = blocks.slice(0, 3).map((b) => b.title || b.type || 'block');
    return labels.length ? labels.join(' / ') : 'Empty';
}

function updateActiveThumb() {
    const active = tabList.querySelector('.screen-card.active .screen-thumb');
    if (active) {
        active.innerHTML = renderScreenThumb(state.activeTab);
    }
}

function renderPalette() {
    paletteBlocks.innerHTML = '';
    BLOCK_TYPES.forEach((type) => {
        const btn = document.createElement('div');
        btn.className = 'palette-item';
        btn.setAttribute('draggable', 'true');
        btn.dataset.blockType = type.value;
        btn.textContent = type.label;
        btn.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', JSON.stringify({ source: 'palette', type: type.value }));
        });
        paletteBlocks.appendChild(btn);
    });
}

function getBlocks() {
    return state.layouts[state.activeTab] || [];
}

function createDefaultBlock(type) {
    if (type === 'label') return { type: 'label', title: 'ラベル' };
    if (type === 'hero') return { type: 'hero', title: 'タイトル', body: '説明文', image_url: '', button_label: 'ボタン', button_action: 'tab', button_target: 'products' };
    if (type === 'image') return { type: 'image', title: '画像', body: '', image_url: '' };
    if (type === 'button') return { type: 'button', title: 'ボタン', body: '', button_label: 'クリック', button_action: 'tab', button_target: 'home' };
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
                <button type="button" class="btn btn-secondary btn-sm" data-action="up">↑</button>
                <button type="button" class="btn btn-secondary btn-sm" data-action="down">↓</button>
                <button type="button" class="btn btn-secondary btn-sm" data-action="duplicate">複製</button>
                <button type="button" class="btn btn-secondary btn-sm" data-action="toggle">${block.hidden ? '表示' : '非表示'}</button>
                <button type="button" class="btn btn-secondary btn-sm" data-action="lock">${block.locked ? '解除' : 'ロック'}</button>
                <button type="button" class="btn btn-danger btn-sm" data-action="remove">削除</button>
            </div>
        `;
        card.addEventListener('click', (e) => {
            if (e.target && e.target.dataset.action) return;
            state.activeIndex = idx;
            openDrawer();
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
        card.querySelector('[data-action="up"]').addEventListener('click', (e) => {
            e.stopPropagation();
            if (idx === 0) return;
            const moved = blocks.splice(idx, 1)[0];
            blocks.splice(idx - 1, 0, moved);
            state.activeIndex = idx - 1;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.querySelector('[data-action="down"]').addEventListener('click', (e) => {
            e.stopPropagation();
            if (idx >= blocks.length - 1) return;
            const moved = blocks.splice(idx, 1)[0];
            blocks.splice(idx + 1, 0, moved);
            state.activeIndex = idx + 1;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.querySelector('[data-action="duplicate"]').addEventListener('click', (e) => {
            e.stopPropagation();
            const copy = JSON.parse(JSON.stringify(block));
            blocks.splice(idx + 1, 0, copy);
            state.activeIndex = idx + 1;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.querySelector('[data-action="toggle"]').addEventListener('click', (e) => {
            e.stopPropagation();
            block.hidden = !block.hidden;
            renderBlockList();
            renderPreview();
        });
        card.querySelector('[data-action="lock"]').addEventListener('click', (e) => {
            e.stopPropagation();
            block.locked = !block.locked;
            renderBlockList();
        });
        card.addEventListener('dragstart', (e) => {
            if (block.locked) return e.preventDefault();
            e.dataTransfer.setData('text/plain', JSON.stringify({ source: 'list', index: idx }));
        });
        card.addEventListener('dragover', (e) => e.preventDefault());
        card.addEventListener('drop', (e) => {
            e.preventDefault();
            const payload = e.dataTransfer.getData('text/plain');
            let data;
            try { data = JSON.parse(payload); } catch { return; }
            if (!data || typeof data.index !== 'number') return;
            const from = data.index;
            const to = idx;
            if (Number.isNaN(from) || from === to) return;
            const moved = blocks.splice(from, 1)[0];
            blocks.splice(to, 0, moved);
            state.activeIndex = to;
            renderBlockList();
            renderEditor();
            renderPreview();
        });
        card.addEventListener('dragenter', () => {
            card.classList.add('drag-over');
        });
        card.addEventListener('dragleave', () => {
            card.classList.remove('drag-over');
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
    if (block.locked) {
        blockEditor.innerHTML = '<div class="text-muted">このブロックはロックされています。</div>';
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
            <div style="display:flex; gap:8px; margin-bottom:8px;">
                <input type="text" class="form-control" id="editButtonLabel" placeholder="ラベル" value="${escapeHtml(block.button_label || '')}">
            </div>
            <div style="display:flex; gap:8px;">
                <select class="form-control" id="editButtonAction">
                    <option value="none" ${block.button_action === 'none' ? 'selected' : ''}>なし</option>
                    <option value="tab" ${block.button_action === 'tab' ? 'selected' : ''}>タブ切替</option>
                    <option value="url" ${block.button_action === 'url' ? 'selected' : ''}>URL</option>
                </select>
                <input type="text" class="form-control" id="editButtonTarget" placeholder="tab名 or URL" value="${escapeHtml(block.button_target || '')}">
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
    const buttonActionEl = blockEditor.querySelector('#editButtonAction');
    const buttonTargetEl = blockEditor.querySelector('#editButtonTarget');
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
        block.button_action = buttonActionEl.value;
        block.button_target = buttonTargetEl.value;
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

    [typeEl, titleEl, bodyEl, imageEl, buttonLabelEl, buttonActionEl, buttonTargetEl, listEl, sliderEl, sourceEl, paddingYEl, paddingXEl, alignEl, bgEl, textColorEl, borderColorEl, borderWidthEl, radiusEl, heightEl, formButtonEl]
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
    show('#editButtonAction', ['hero', 'button'].includes(type));
    show('#editButtonTarget', ['hero', 'button'].includes(type));
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
        previewArea.innerHTML = '<div class="drop-hint">左からブロックをドラッグして追加してください</div>';
        return;
    }
    previewArea.innerHTML = `<div class="builder-container">${blocks.map((block, idx) => renderPreviewBlock(block, idx)).join('')}</div>`;
    bindPreviewInteractions();
    applyScreenSettings();
    updateActiveThumb();
}

function renderPreviewBlock(block, idx) {
    if (block.hidden) return '';
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
        const idx = parseInt(blockEl.dataset.index, 10);
        const blocks = getBlocks();
        const block = blocks[idx];
        blockEl.addEventListener('dragstart', (e) => {
            if (block && block.locked) return e.preventDefault();
            blockEl.classList.add('dragging');
            e.dataTransfer.setData('text/plain', JSON.stringify({ source: 'canvas', index: idx }));
        });
        blockEl.addEventListener('dragend', () => blockEl.classList.remove('dragging'));
        blockEl.addEventListener('dragover', (e) => e.preventDefault());
        blockEl.addEventListener('drop', (e) => {
            e.preventDefault();
            if (block && block.locked) return;
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
            if (!blocks[idx] || blocks[idx].locked || !field) return;
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
            if (!blocks[idx] || blocks[idx].locked) return;
            blocks[idx].image_url = input.value;
            renderBlockList();
            renderPreview();
        });
    });
}

function openDrawer() {
    if (propertyDrawer) propertyDrawer.classList.add('is-open');
}

function closeDrawer() {
    if (propertyDrawer) propertyDrawer.classList.remove('is-open');
}

function applyScreenSettings() {
    const settings = state.tabSettings[state.activeTab] || {};
    if (screenBgColor) screenBgColor.value = settings.background_color || '#ffffff';
    if (screenBgImage) screenBgImage.value = settings.background_image || '';
    if (screenBgSize) screenBgSize.value = settings.background_size || 'cover';
    if (screenBgPos) screenBgPos.value = settings.background_position || 'center';
    if (screenMaxWidth) screenMaxWidth.value = settings.content_max_width || 440;
    if (screenPadding) screenPadding.value = settings.content_padding || 20;

    if (previewArea) {
        previewArea.style.background = settings.background_color || '#f5f6fb';
        if (settings.background_image) {
            previewArea.style.backgroundImage = `url('${settings.background_image}')`;
            previewArea.style.backgroundSize = settings.background_size || 'cover';
            previewArea.style.backgroundPosition = settings.background_position || 'center';
            previewArea.style.backgroundRepeat = 'no-repeat';
        } else {
            previewArea.style.backgroundImage = '';
        }
    }
}

function bindScreenSettings() {
    const update = () => {
        const settings = state.tabSettings[state.activeTab] || {};
        settings.background_color = screenBgColor?.value || '';
        settings.background_image = screenBgImage?.value || '';
        settings.background_size = screenBgSize?.value || 'cover';
        settings.background_position = screenBgPos?.value || 'center';
        settings.content_max_width = parseInt(screenMaxWidth?.value || '440', 10);
        settings.content_padding = parseInt(screenPadding?.value || '20', 10);
        state.tabSettings[state.activeTab] = settings;
        renderPreview();
        renderTabs();
    };
    [screenBgColor, screenBgImage, screenBgSize, screenBgPos, screenMaxWidth, screenPadding].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', update);
        el.addEventListener('change', update);
    });
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    if (customCssInput && customCssEditor) {
        customCssInput.value = customCssEditor.value;
    }
    if (tabSettingsJson) {
        tabSettingsJson.value = JSON.stringify(state.tabSettings);
    }
});

if (closeDrawerBtn) {
    closeDrawerBtn.addEventListener('click', closeDrawer);
}

function initDnD() {
    const dropIndicator = document.createElement('div');
    dropIndicator.className = 'drop-indicator';

    const getDropIndex = (e) => {
        const blocks = [...previewArea.querySelectorAll('.preview-block')];
        if (!blocks.length) return 0;
        for (let i = 0; i < blocks.length; i += 1) {
            const rect = blocks[i].getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) return i;
        }
        return blocks.length;
    };

    const showIndicator = (index) => {
        const blocks = previewArea.querySelectorAll('.preview-block');
        if (blocks.length === 0) {
            previewArea.appendChild(dropIndicator);
            return;
        }
        if (index >= blocks.length) {
            blocks[blocks.length - 1].after(dropIndicator);
        } else {
            blocks[index].before(dropIndicator);
        }
    };

    const handleDrop = (indexOverride) => (e) => {
        e.preventDefault();
        previewArea.classList.remove('is-over');
        const payload = e.dataTransfer.getData('text/plain');
        if (!payload) return;
        let data;
        try { data = JSON.parse(payload); } catch { return; }
        const index = typeof indexOverride === 'number' ? indexOverride : getDropIndex(e);
        if (data.source === 'palette') {
            const blocks = getBlocks();
            const newBlock = createDefaultBlock(data.type);
            blocks.splice(index, 0, newBlock);
            state.activeIndex = blocks.indexOf(newBlock);
            openDrawer();
            renderBlockList();
            renderEditor();
            renderPreview();
        } else if (data.source === 'canvas' || data.source === 'list') {
            const blocks = getBlocks();
            const from = data.index;
            if (typeof from !== 'number') return;
            const moved = blocks.splice(from, 1)[0];
            const targetIndex = index > from ? index - 1 : index;
            blocks.splice(targetIndex, 0, moved);
            state.activeIndex = targetIndex;
            renderBlockList();
            renderEditor();
            renderPreview();
        }
        dropIndicator.remove();
    };

    previewArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        previewArea.classList.add('is-over');
        showIndicator(getDropIndex(e));
    });
    previewArea.addEventListener('dragleave', () => {
        previewArea.classList.remove('is-over');
        dropIndicator.remove();
    });
    previewArea.addEventListener('drop', handleDrop());

    blockList.addEventListener('dragover', (e) => e.preventDefault());
    blockList.addEventListener('drop', handleDrop());
}

document.querySelectorAll('.template-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.templateId;
        const template = templates.find((t) => t.id === id);
        if (!template) return;
        state.layouts[state.activeTab] = JSON.parse(JSON.stringify(template.blocks || []));
        state.activeIndex = 0;
        renderBlockList();
        renderEditor();
        renderPreview();
        openDrawer();
    });
});
if (toggleCssBtn && cssPanel) {
    toggleCssBtn.addEventListener('click', () => {
        cssPanel.classList.toggle('is-open');
    });
}

if (customCssEditor) {
    const styleTag = document.getElementById('designer-custom-style') || (() => {
        const style = document.createElement('style');
        style.id = 'designer-custom-style';
        document.head.appendChild(style);
        return style;
    })();
    const updateCss = () => {
        styleTag.textContent = customCssEditor.value || '';
    };
    customCssEditor.addEventListener('input', updateCss);
    updateCss();
}

function bindResizers() {
    if (!designerMain) return;
    const startDrag = (resizer, side) => {
        let startX = 0;
        let startLeft = 240;
        let startRight = 320;
        const onMouseMove = (e) => {
            const dx = e.clientX - startX;
            if (side === 'left') {
                const next = Math.max(180, Math.min(360, startLeft + dx));
                designerMain.style.setProperty('--left-width', `${next}px`);
            } else {
                const next = Math.max(240, Math.min(420, startRight - dx));
                designerMain.style.setProperty('--right-width', `${next}px`);
            }
        };
        const onMouseUp = () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };
        resizer.addEventListener('mousedown', (e) => {
            e.preventDefault();
            startX = e.clientX;
            startLeft = parseInt(getComputedStyle(designerMain).getPropertyValue('--left-width')) || 240;
            startRight = parseInt(getComputedStyle(designerMain).getPropertyValue('--right-width')) || 320;
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    };
    if (leftResizer) startDrag(leftResizer, 'left');
    if (rightResizer) startDrag(rightResizer, 'right');
}

renderTabs();
renderPalette();
renderBlockList();
renderEditor();
renderPreview();
bindScreenSettings();
bindResizers();
initDnD();
</script>
@endsection
