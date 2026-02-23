@extends('admin.layout')

@section('title', 'LIFFデザイン')
@section('page-title', 'LIFFデザイン')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/liff-designer.css') }}?v={{ time() }}">
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
        <h1 class="ld-topbar__title"><i class="fas fa-palette"></i> LINE画面デザイン</h1>
        <div class="ld-topbar__actions">
            <button type="button" class="ld-btn ld-btn--outline" id="templateBtn"><i class="fas fa-layer-group"></i> テンプレート</button>
            <button type="button" class="ld-btn ld-btn--ghost" id="importBtn" title="JSONを読み込み"><i class="fas fa-file-import"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="exportBtn" title="JSONを書き出し"><i class="fas fa-file-export"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="undoBtn" title="元に戻す" disabled><i class="fas fa-undo"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="redoBtn" title="やり直す" disabled><i class="fas fa-redo"></i></button>
            <button type="button" class="ld-btn ld-btn--outline" id="previewBtn"><i class="fas fa-eye"></i> プレビュー</button>
            <button type="submit" class="ld-btn ld-btn--primary"><i class="fas fa-save"></i> 保存する</button>
        </div>
    </div>
    <div class="alert alert-info" role="alert" style="margin: 10px 14px 0;">
        <i class="fas fa-info-circle"></i>
        LIFF ID / URL はプロジェクトごとに設定できます。未設定時のみ <code>.env</code>（<code>LINE_LIFF_ID</code> / <code>LINE_LIFF_URL</code>）を使用します。
    </div>
    <div class="card" style="margin: 10px 14px 0;">
        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;">
            <div>
                <label class="ld-form-label" for="lineLiffId">LIFF ID（このプロジェクト）</label>
                <input type="text" id="lineLiffId" name="line_liff_id" class="ld-form-input" value="{{ old('line_liff_id', $liffId ?? '') }}" placeholder="例: 2001234567-AbCdEfGh">
            </div>
            <div>
                <label class="ld-form-label" for="lineLiffUrl">LIFF URL（このプロジェクト）</label>
                <input type="url" id="lineLiffUrl" name="line_liff_url" class="ld-form-input" value="{{ old('line_liff_url', $liffUrl ?? '') }}" placeholder="例: https://example.com/liff/default">
            </div>
        </div>
    </div>

    <div class="ld-body">
        {{-- 左: 画面タブ --}}
        <aside class="ld-screens">
            <div class="ld-screens__title">画面を選択</div>
            <div class="ld-screens__list" id="screenList"></div>
        </aside>

        {{-- 中央: スマホプレビュー --}}
        <div class="ld-editor">
            <div class="ld-editor__toolbar">
                <div class="ld-editor__meta">
                    <span class="ld-editor__meta-label">現在のタブ</span>
                    <strong id="activeTabLabel">ホーム</strong>
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
            {{-- ブロック追加パレット（常に表示） --}}
            <div class="ld-sidebar__section ld-sidebar__section--palette" id="addBlockSection">
                <div class="ld-sidebar__title"><i class="fas fa-plus-circle"></i> ブロックを追加</div>
                <div class="ld-palette-controls">
                    <input type="text" id="paletteSearch" class="ld-form-input" placeholder="ブロック検索 (例: 画像)">
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

<script src="{{ asset('js/admin/liff-designer.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new LiffDesigner({
            layouts: @json($layouts ?? []),
            tabSettings: @json($tabSettings ?? []),
            templates: @json($templates ?? []),
            customCss: @json($customCss ?? ''),
            featureFlags: @json($featureFlags ?? [])
        });
    });
</script>
@endsection
