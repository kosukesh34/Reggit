@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/liff-designer.css') }}?v={{ time() }}">
<style>
.ld-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px);
    background: #065f46; color: #fff; padding: 12px 24px; border-radius: 12px;
    font-size: 14px; font-weight: 600; z-index: 99999; box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    opacity: 0; transition: all 0.3s ease; pointer-events: none;
    display: flex; align-items: center; gap: 8px;
}
.ld-toast--visible { opacity: 1; transform: translateX(-50%) translateY(0); }
.ld-toast--error { background: #991b1b; }

.ld-device-switcher {
    display: flex; align-items: center; gap: 4px; background: #f3f4f6; border-radius: 8px; padding: 3px;
}
.ld-device-btn {
    padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;
    border: none; cursor: pointer; background: transparent; color: #6b7280;
    display: flex; align-items: center; gap: 4px; transition: all 0.15s;
}
.ld-device-btn:hover { color: #374151; }
.ld-device-btn.is-active { background: #fff; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

.ld-platform-badges {
    display: flex; align-items: center; gap: 4px; margin-left: 6px;
}
.ld-platform-badge {
    font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
}
.ld-platform-badge--line { background: #06C755; color: #fff; }
.ld-platform-badge--native { background: #3b82f6; color: #fff; }

.ld-settings-panel {
    display: none; position: absolute; top: 0; right: 0; bottom: 0; width: 320px;
    background: #fff; border-left: 1px solid #e5e7eb; z-index: 20; box-shadow: -4px 0 20px rgba(0,0,0,0.05);
}
.ld-settings-panel--open { display: flex; flex-direction: column; }

.ld-liff-settings-toggle {
    cursor: pointer; user-select: none; transition: background 0.15s; border-radius: 8px; padding: 8px;
}
.ld-liff-settings-toggle:hover { background: #f9fafb; }
.ld-liff-settings-toggle__header {
    display: flex; align-items: center; justify-content: space-between;
}
.ld-liff-settings-toggle__title {
    font-size: 11px; font-weight: 700; color: #374151; display: flex; align-items: center; gap: 6px;
}
.ld-liff-settings-toggle__content {
    display: none; padding-top: 10px;
}
.ld-liff-settings-toggle.is-open .ld-liff-settings-toggle__content { display: block; }
.ld-liff-settings-toggle__chevron {
    transition: transform 0.2s; font-size: 11px; color: #9ca3af;
}
.ld-liff-settings-toggle.is-open .ld-liff-settings-toggle__chevron { transform: rotate(180deg); }

.ld-quickstart {
    display: flex; flex-direction: column; align-items: center; padding: 40px 24px; text-align: center;
    gap: 16px; min-height: 300px; justify-content: center;
}
.ld-quickstart__icon { font-size: 48px; color: #c7d2fe; }
.ld-quickstart__title { font-size: 16px; font-weight: 700; color: #374151; }
.ld-quickstart__desc { font-size: 13px; color: #6b7280; line-height: 1.6; max-width: 280px; }
.ld-quickstart__actions { display: flex; flex-direction: column; gap: 8px; width: 100%; max-width: 240px; }
</style>
@endpush

@section('content')
<form action="{{ admin_route('admin.settings.liff-designer.update') }}" method="POST" id="liffDesignerForm" class="ld">
    @csrf
    <input type="hidden" name="layouts_json" id="layoutsJson">
    <input type="hidden" name="custom_css" id="customCssInput" value="{{ $customCss ?? '' }}">
    <input type="hidden" name="tab_settings_json" id="tabSettingsJson">
    <input type="file" id="importJsonFile" accept="application/json" hidden>

    <div class="ld-topbar">
        <a href="{{ admin_route('admin.settings.basic') }}" class="ld-btn ld-btn--ghost" title="設定に戻る"><i class="fas fa-arrow-left"></i></a>
        <h1 class="ld-topbar__title">
            <i class="fas fa-palette"></i> ノーコードUIデザイナー
            <span class="ld-platform-badges">
                <span class="ld-platform-badge ld-platform-badge--line">LINE</span>
                <span class="ld-platform-badge ld-platform-badge--native">ネイティブ</span>
            </span>
        </h1>
        <div class="ld-topbar__actions">
            <button type="button" class="ld-btn ld-btn--outline" id="templateBtn"><i class="fas fa-layer-group"></i> テンプレート</button>
            <button type="button" class="ld-btn ld-btn--ghost" id="importBtn" title="JSONを読み込み"><i class="fas fa-file-import"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="exportBtn" title="JSONを書き出し"><i class="fas fa-file-export"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="undoBtn" title="元に戻す (Ctrl+Z)" disabled><i class="fas fa-undo"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="redoBtn" title="やり直す (Ctrl+Shift+Z)" disabled><i class="fas fa-redo"></i></button>
            <button type="button" class="ld-btn ld-btn--outline" id="previewBtn"><i class="fas fa-eye"></i> プレビュー</button>
            <button type="submit" class="ld-btn ld-btn--primary" id="saveBtn"><i class="fas fa-save"></i> 保存</button>
        </div>
    </div>

    <div class="ld-body">
        {{-- 左: 画面タブ --}}
        <aside class="ld-screens">
            <div class="ld-screens__title">画面（タブ）</div>
            <div class="ld-screens__list" id="screenList"></div>
            <div class="ld-screens__title ld-screens__title--templates">テンプレート</div>
            <div class="ld-tab-templates" id="tabTemplateList"></div>

            {{-- LIFF設定（折りたたみ） --}}
            <div class="ld-liff-settings-toggle" id="liffSettingsToggle">
                <div class="ld-liff-settings-toggle__header">
                    <span class="ld-liff-settings-toggle__title"><i class="fas fa-cog"></i> LIFF設定</span>
                    <i class="fas fa-chevron-down ld-liff-settings-toggle__chevron"></i>
                </div>
                <div class="ld-liff-settings-toggle__content" onclick="event.stopPropagation()">
                    <div class="ld-form-group">
                        <label class="ld-form-label" for="lineLiffId">LIFF ID</label>
                        <input type="text" id="lineLiffId" name="line_liff_id" class="ld-form-input" value="{{ old('line_liff_id', $liffId ?? '') }}" placeholder="例: 2001234567-AbCdEfGh">
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label" for="lineLiffUrl">LIFF URL</label>
                        <input type="url" id="lineLiffUrl" name="line_liff_url" class="ld-form-input" value="{{ old('line_liff_url', $liffUrl ?? '') }}" placeholder="例: https://example.com/liff/default">
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label" for="lineFriendAddUrl">友だち追加URL</label>
                        <input type="url" id="lineFriendAddUrl" name="line_friend_add_url" class="ld-form-input" value="{{ old('line_friend_add_url', $lineFriendAddUrl ?? '') }}" placeholder="例: https://line.me/R/ti/p/@ID">
                    </div>
                </div>
            </div>
        </aside>

        {{-- 中央: スマホプレビュー --}}
        <div class="ld-editor">
            <div class="ld-editor__toolbar">
                <div class="ld-editor__meta">
                    <span class="ld-editor__meta-label">現在のタブ</span>
                    <strong id="activeTabLabel">ホーム</strong>
                </div>
                <div class="ld-device-switcher" id="deviceSwitcher">
                    <button type="button" class="ld-device-btn is-active" data-device="line"><i class="fab fa-line"></i> LINE</button>
                    <button type="button" class="ld-device-btn" data-device="ios"><i class="fab fa-apple"></i> iOS</button>
                    <button type="button" class="ld-device-btn" data-device="android"><i class="fab fa-android"></i> Android</button>
                </div>
                <div class="ld-editor__zoom">
                    <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="zoomOutBtn"><i class="fas fa-search-minus"></i></button>
                    <input type="range" id="zoomRange" class="ld-form-range ld-form-range--zoom" min="60" max="120" value="100">
                    <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="zoomInBtn"><i class="fas fa-search-plus"></i></button>
                    <span id="zoomValue" class="ld-editor__zoom-value">100%</span>
                </div>
            </div>
            <div class="ld-phone">
                <div class="ld-phone__frame">
                    <div class="ld-phone__notch"></div>
                    <div class="ld-phone__header" id="phoneHeader">
                        <span class="ld-phone__header-title" id="phoneHeaderTitle">ホーム</span>
                    </div>
                    <div class="ld-phone__body" id="phoneBody">
                        <div class="ld-phone__content" id="phoneContent"></div>
                    </div>
                    <div class="ld-phone__nav" id="phoneNav"></div>
                </div>
            </div>
        </div>

        {{-- 右: ブロック追加（常時表示）+ 設定 --}}
        <aside class="ld-sidebar">
            {{-- ブロック追加パレット --}}
            <div class="ld-sidebar__section ld-sidebar__section--palette" id="addBlockSection">
                <div class="ld-sidebar__title"><i class="fas fa-plus-circle"></i> ブロックを追加</div>
                <div class="ld-palette-controls">
                    <input type="text" id="paletteSearch" class="ld-form-input" placeholder="ブロック名で検索...">
                    <div class="ld-palette-categories" id="paletteCategories"></div>
                </div>
                <div class="ld-block-palette" id="blockPalette"></div>
            </div>

            {{-- ブロック設定パネル --}}
            <div class="ld-sidebar__section ld-sidebar__section--settings" id="blockSettingsSection" hidden>
                <div class="ld-sidebar__title-row">
                    <span class="ld-sidebar__title"><i class="fas fa-sliders-h"></i> ブロック設定</span>
                    <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="closeSettings"><i class="fas fa-times"></i></button>
                </div>
                <div id="blockSettings"></div>
            </div>

            {{-- 画面設定 --}}
            <div class="ld-sidebar__section">
                <div class="ld-sidebar__title"><i class="fas fa-cog"></i> 画面設定</div>
                <div class="ld-form-group">
                    <label class="ld-form-label">背景色</label>
                    <div class="ld-color-row">
                        <input type="color" id="screenBgColor" class="ld-color-picker" value="#f5f5f5">
                        <input type="text" id="screenBgColorHex" class="ld-form-input ld-form-input--sm" value="#f5f5f5" maxlength="7">
                    </div>
                </div>
                <div class="ld-form-group">
                    <label class="ld-form-label">背景画像</label>
                    <input type="text" id="screenBgImage" class="ld-form-input" placeholder="https://example.com/bg.jpg">
                </div>
            </div>

            {{-- カスタムCSS --}}
            <div class="ld-sidebar__section" id="cssSection" hidden>
                <div class="ld-sidebar__title"><i class="fas fa-code"></i> カスタムCSS</div>
                <textarea id="customCssEditor" class="ld-form-textarea" rows="6" placeholder=".my-class { color: red; }">{{ $customCss ?? '' }}</textarea>
            </div>
            <button type="button" class="ld-btn ld-btn--ghost ld-btn--block" id="toggleCssBtn">
                <i class="fas fa-code"></i> カスタムCSS
            </button>
        </aside>
    </div>
</form>

<div class="ld-toast" id="ldToast"></div>

<script src="{{ asset('js/admin/liff-designer/modules/data-preview.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/template-manager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/drag-drop.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/ui-events.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/io.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var designer = new LiffDesigner({
            layouts: @json($layouts ?? []),
            tabSettings: @json($tabSettings ?? []),
            templates: @json($templates ?? []),
            customCss: @json($customCss ?? ''),
            featureFlags: @json($featureFlags ?? [])
        });

        // デバイスプレビュー切替
        document.getElementById('deviceSwitcher').addEventListener('click', function(e) {
            var btn = e.target.closest('.ld-device-btn');
            if (!btn) return;
            document.querySelectorAll('.ld-device-btn').forEach(function(b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            var device = btn.dataset.device;
            designer.ui.previewDevice = device;
            designer.applyPreviewDevice();
        });

        // LIFF設定折りたたみ
        document.getElementById('liffSettingsToggle').addEventListener('click', function(e) {
            if (e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return;
            this.classList.toggle('is-open');
        });

        // 保存フィードバック
        var form = document.getElementById('liffDesignerForm');
        form.addEventListener('submit', function() {
            setTimeout(function() {
                showToast('デザインを保存しました');
            }, 100);
        });
    });

    function showToast(msg, isError) {
        var el = document.getElementById('ldToast');
        el.innerHTML = '<i class="fas ' + (isError ? 'fa-exclamation-circle' : 'fa-check-circle') + '"></i> ' + msg;
        el.className = 'ld-toast' + (isError ? ' ld-toast--error' : '');
        requestAnimationFrame(function() { el.classList.add('ld-toast--visible'); });
        setTimeout(function() { el.classList.remove('ld-toast--visible'); }, 3000);
    }
</script>
@endsection
