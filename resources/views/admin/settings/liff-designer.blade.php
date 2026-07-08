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
    <input type="hidden" name="menu_items_json" id="menuItemsJson">
    <input type="file" id="importJsonFile" accept="application/json" hidden>

    <div class="ld-topbar">
        <a href="{{ admin_route('admin.settings.basic') }}" class="ld-btn ld-btn--ghost" title="設定に戻る"><i class="fas fa-arrow-left"></i></a>
        <h1 class="ld-topbar__title">
            <i class="fas fa-palette"></i> ノーコードUIデザイナー
        </h1>
        <div class="ld-topbar__layout" title="パネル幅プリセット（ドラッグでも調整できます）">
            <span class="ld-topbar__layout-label">レイアウト</span>
            <button type="button" class="ld-panel-preset" data-panel-preset="compact" title="コンパクト">狭</button>
            <button type="button" class="ld-panel-preset is-active" data-panel-preset="standard" title="標準">標準</button>
            <button type="button" class="ld-panel-preset" data-panel-preset="wide" title="ワイド">広</button>
            <button type="button" class="ld-panel-preset" data-panel-preset="focus" title="プレビュー重視">集中</button>
            <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="resetPanelWidths" title="パネル幅をリセット"><i class="fas fa-arrows-alt-h"></i></button>
        </div>
        <div class="ld-topbar__actions">
            <button type="button" class="ld-btn ld-btn--outline ld-mobile-panel-btn" id="openScreensDrawer" title="画面・テンプレート"><i class="fas fa-th-list"></i></button>
            <button type="button" class="ld-btn ld-btn--outline ld-mobile-panel-btn" id="openBlocksDrawer" title="ブロック追加"><i class="fas fa-plus-circle"></i></button>
            <button type="button" class="ld-btn ld-btn--outline" id="templateBtn"><i class="fas fa-layer-group"></i> テンプレート</button>
            <button type="button" class="ld-btn ld-btn--ghost" id="importBtn" title="JSONを読み込み"><i class="fas fa-file-import"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="exportBtn" title="JSONを書き出し"><i class="fas fa-file-export"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="undoBtn" title="元に戻す (Ctrl+Z)" disabled><i class="fas fa-undo"></i></button>
            <button type="button" class="ld-btn ld-btn--ghost" id="redoBtn" title="やり直す (Ctrl+Shift+Z)" disabled><i class="fas fa-redo"></i></button>
            <button type="button" class="ld-btn ld-btn--outline" id="previewBtn"><i class="fas fa-eye"></i> プレビュー</button>
            <button type="button" class="ld-help-btn" id="helpBtn" title="使い方ガイド"><i class="fas fa-question"></i></button>
            <button type="submit" class="ld-btn ld-btn--primary" id="saveBtn"><i class="fas fa-save"></i> 保存</button>
        </div>
    </div>

    <div class="ld-body">
        {{-- 左: 画面タブ --}}
        <aside class="ld-screens">
            <div class="ld-screens__title">画面（タブ）</div>
            <p class="ld-screens__hint">ドラッグで並び替え・表示切替・名前変更。アプリとLINEに反映されます。</p>
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

        <div class="ld-resizer ld-resizer--left" data-resizer="left" role="separator" aria-orientation="vertical" aria-label="左パネル幅を調整" title="ドラッグで左パネル幅を調整（ダブルクリックでリセット）"></div>

        {{-- 中央: スマホプレビュー --}}
        <div class="ld-editor">
            <div class="ld-editor__toolbar">
                <div class="ld-editor__meta">
                    <span class="ld-editor__meta-label">プレビュー</span>
                    <strong id="activeTabLabel">ホーム</strong>
                </div>
                <div class="ld-editor__zoom">
                    <button type="button" class="ld-btn ld-btn--primary ld-btn--xs" id="quickTemplateBtn" title="テンプレートを選んで適用"><i class="fas fa-layer-group"></i> テンプレート</button>
                    <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="zoomOutBtn"><i class="fas fa-search-minus"></i></button>
                    <input type="range" id="zoomRange" class="ld-form-range ld-form-range--zoom" min="60" max="120" value="100">
                    <button type="button" class="ld-btn ld-btn--ghost ld-btn--xs" id="zoomInBtn"><i class="fas fa-search-plus"></i></button>
                    <span id="zoomValue" class="ld-editor__zoom-value">100%</span>
                </div>
            </div>
            <div class="ld-phone">
                <div class="ld-phone__frame is-line">
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

        <div class="ld-resizer ld-resizer--right" data-resizer="right" role="separator" aria-orientation="vertical" aria-label="右パネル幅を調整" title="ドラッグで右パネル幅を調整（ダブルクリックでリセット）"></div>

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
                        <input type="color" id="screenBgColor" class="ld-color-picker" value="#fafaf9">
                        <input type="text" id="screenBgColorHex" class="ld-form-input ld-form-input--sm" value="#fafaf9" maxlength="7">
                    </div>
                </div>
                <div class="ld-form-group">
                    <label class="ld-form-label">背景画像 URL</label>
                    <input type="text" id="screenBgImage" class="ld-form-input" placeholder="https://example.com/bg.jpg">
                </div>
                <div class="ld-form-group">
                    <label class="ld-form-label">背景画像の表示</label>
                    <select id="screenBgSize" class="ld-form-select">
                        <option value="cover">カバー（全体を覆う）</option>
                        <option value="contain">全体表示</option>
                        <option value="auto">オリジナルサイズ</option>
                    </select>
                </div>
                <div class="ld-form-group">
                    <label class="ld-form-label">コンテンツ余白 (px)</label>
                    <input type="range" id="screenContentPadding" class="ld-form-range" min="8" max="40" value="18">
                    <span id="screenContentPaddingVal" class="ld-range-val">18</span>
                </div>
            </div>

            {{-- テーマ（SaaS テーマプリセット + 一括カスタマイズ） --}}
            <div class="ld-sidebar__section">
                <div class="ld-sidebar__title"><i class="fas fa-swatchbook"></i> テーマ</div>
                <div class="ld-design-panel">
                    <div class="ld-form-group">
                        <label class="ld-form-label">テーマプリセット</label>
                        <p class="ld-form-hint">業態別の完成デザイン。配色・背景・幅を一括適用できます。</p>
                        <div class="ld-theme-gallery" id="designPresets"></div>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">コンテンツ幅</label>
                        <select id="globalContentWidth" class="ld-form-select">
                            @foreach(\App\Support\LiffThemePresets::contentWidths() as $key => $width)
                            <option value="{{ $key }}">{{ $width['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">ページ背景</label>
                        <div class="ld-bg-style-tabs">
                            @foreach(\App\Support\LiffThemePresets::backgroundStyles() as $key => $label)
                            <button type="button" class="ld-bg-style-tab" data-bg-style="{{ $key }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        <div id="bgPanelSolid" class="ld-bg-panel">
                            <label class="ld-form-label">背景色</label>
                            <div class="ld-color-row">
                                <input type="color" id="globalBgColor" class="ld-color-picker" value="#f9fafb">
                                <input type="text" id="globalBgColorHex" class="ld-form-input ld-form-input--sm" value="#f9fafb" maxlength="7">
                            </div>
                        </div>
                        <div id="bgPanelGradient" class="ld-bg-panel" hidden>
                            <label class="ld-form-label">グラデーション開始</label>
                            <div class="ld-color-row">
                                <input type="color" id="globalBgGradientStart" class="ld-color-picker" value="#eef2ff">
                                <input type="text" id="globalBgGradientStartHex" class="ld-form-input ld-form-input--sm" value="#eef2ff" maxlength="7">
                            </div>
                            <label class="ld-form-label">グラデーション終了</label>
                            <div class="ld-color-row">
                                <input type="color" id="globalBgGradientEnd" class="ld-color-picker" value="#ffffff">
                                <input type="text" id="globalBgGradientEndHex" class="ld-form-input ld-form-input--sm" value="#ffffff" maxlength="7">
                            </div>
                        </div>
                        <div id="bgPanelImage" class="ld-bg-panel" hidden>
                            <label class="ld-form-label">背景画像 URL（https）</label>
                            <input type="url" id="globalBgImageUrl" class="ld-form-input" placeholder="https://images.unsplash.com/...">
                            <p class="ld-form-hint">推奨: 1600px幅・1MB以下。読込失敗時は背景色にフォールバックします。</p>
                            <label class="ld-form-label">オーバーレイ不透明度</label>
                            <input type="range" id="globalBgOverlayOpacity" class="ld-form-range" min="0" max="80" value="60">
                            <span id="globalBgOverlayOpacityVal" class="ld-range-val">60</span>
                        </div>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">アクセントカラー</label>
                        <div class="ld-color-row">
                            <input type="color" id="globalAccentColor" class="ld-color-picker" value="#1c1917">
                            <input type="text" id="globalAccentColorHex" class="ld-form-input ld-form-input--sm" value="#1c1917" maxlength="7">
                        </div>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">フォント</label>
                        <select id="globalFontFamily" class="ld-form-select">
                            <option value="">（プリセット標準）</option>
                            @foreach(\App\Support\LiffThemePresets::fontStacks() as $key => $font)
                            <option value="{{ $key }}">{{ $font['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">背景トーン</label>
                        <select id="globalSurfaceTone" class="ld-form-select">
                            <option value="">（プリセット標準）</option>
                            @foreach(\App\Support\LiffThemePresets::surfaceTones() as $key => $tone)
                            <option value="{{ $key }}">{{ $tone['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">セクション間隔 (px)</label>
                        <input type="range" id="globalSectionGap" class="ld-form-range" min="4" max="32" value="16">
                        <span id="globalSectionGapVal" class="ld-range-val">16</span>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">角丸スタイル</label>
                        <select id="globalRadiusScale" class="ld-form-select">
                            <option value="compact">コンパクト</option>
                            <option value="default">標準</option>
                            <option value="soft" selected>ソフト</option>
                            <option value="round">ラウンド</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">タブバースタイル</label>
                        <select id="globalTabStyle" class="ld-form-select">
                            <option value="default">標準</option>
                            <option value="pill">ピル型</option>
                            <option value="floating">フローティング</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">カードの質感</label>
                        <select id="globalCardStyle" class="ld-form-select">
                            <option value="">（プリセット標準）</option>
                            <option value="flat">フラット（影なし）</option>
                            <option value="elevated">エレベート（影つき）</option>
                            <option value="outline">アウトライン（枠線）</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">ボタン形状</label>
                        <select id="globalButtonShape" class="ld-form-select">
                            <option value="">（プリセット標準）</option>
                            <option value="rounded">角丸</option>
                            <option value="pill">ピル型</option>
                            <option value="square">スクエア</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">余白の密度</label>
                        <select id="globalDensity" class="ld-form-select">
                            <option value="compact">コンパクト</option>
                            <option value="default" selected>標準</option>
                            <option value="relaxed">ゆったり</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">自動表示UIの雰囲気</label>
                        <p class="ld-form-hint">商品・注文・予約・クーポンなど、システムが自動で出す画面にも反映します。</p>
                        <select id="globalSystemUiStyle" class="ld-form-select">
                            <option value="refined">上質ミニマル</option>
                            <option value="editorial">写真映えリッチ</option>
                            <option value="compact">情報密度高め</option>
                            <option value="luxe">ラグジュアリー</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">自動表示カード</label>
                        <select id="globalSystemCardStyle" class="ld-form-select">
                            <option value="soft">ソフトカード</option>
                            <option value="elevated">浮かせる</option>
                            <option value="outline">細線アウトライン</option>
                            <option value="flat">フラット</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">自動表示画像</label>
                        <select id="globalSystemMediaShape" class="ld-form-select">
                            <option value="rounded">角丸</option>
                            <option value="square">スクエア</option>
                            <option value="arch">アーチ</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">自動表示見出し</label>
                        <select id="globalSystemHeaderStyle" class="ld-form-select">
                            <option value="minimal">ミニマル</option>
                            <option value="editorial">エディトリアル</option>
                            <option value="hero">ヒーロー風</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">会員証レイアウト</label>
                        <select id="globalMemberCardDesign" class="ld-form-select">
                            <option value="">既存の会員設定を使う</option>
                            <option value="default">標準カード</option>
                            <option value="logo_top">ロゴ上部</option>
                            <option value="logo_center">ロゴ中央</option>
                            <option value="logo_header">ロゴヘッダー</option>
                            <option value="compact">コンパクト</option>
                            <option value="split_banner">スプリットバナー</option>
                            <option value="minimal_line">ミニマルライン</option>
                            <option value="emblem">エンブレム</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">会員画面UI</label>
                        <select id="globalMemberScreenDesign" class="ld-form-select">
                            <option value="wallet">ウォレット</option>
                            <option value="premium">プレミアム</option>
                            <option value="minimal">ミニマル</option>
                            <option value="compact">コンパクト</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">クーポン画面UI</label>
                        <select id="globalCouponScreenDesign" class="ld-form-select">
                            <option value="ticket">チケット</option>
                            <option value="gallery">ギャラリー</option>
                            <option value="compact">コンパクト</option>
                            <option value="luxury">ラグジュアリー</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">予約画面UI</label>
                        <select id="globalReservationScreenDesign" class="ld-form-select">
                            <option value="cards">カード</option>
                            <option value="timeline">タイムライン</option>
                            <option value="compact">コンパクト</option>
                            <option value="concierge">コンシェルジュ</option>
                        </select>
                    </div>
                    <div class="ld-form-group">
                        <label class="ld-form-label">注文・順番待ちUI</label>
                        <select id="globalActivityScreenDesign" class="ld-form-select">
                            <option value="cards">カード</option>
                            <option value="timeline">タイムライン</option>
                            <option value="compact">コンパクト</option>
                            <option value="dashboard">ダッシュボード</option>
                        </select>
                    </div>
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

<script src="{{ asset('js/admin/liff-designer/modules/block-designs.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/data-preview.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/theme-panel.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/template-preview.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/template-manager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/drag-drop.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/ui-events.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/io.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/onboarding.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/panel-resizer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer/modules/panel-drawers.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/admin/liff-designer.js') }}?v={{ time() }}"></script>
@php
    $ldDesignerConfig = [
        'layouts' => $layouts ?? [],
        'tabSettings' => $tabSettings ?? [],
        'templates' => $templates ?? [],
        'customCss' => $customCss ?? '',
        'featureFlags' => $featureFlags ?? [],
        'menuItems' => $menuItems ?? [],
        'themePresets' => \App\Support\LiffThemePresets::all(),
        'fontStacks' => \App\Support\LiffThemePresets::fontStacks(),
        'surfaceTones' => \App\Support\LiffThemePresets::surfaceTones(),
    ];
@endphp
<script type="application/json" id="ldDesignerConfig">@json($ldDesignerConfig)</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof LiffDesigner === 'undefined') {
            console.error('LiffDesigner script not loaded');
            if (typeof showToast === 'function') {
                showToast('デザイナーの読み込みに失敗しました。ページを再読み込みしてください。', true);
            }
            return;
        }

        var config = {};
        var configEl = document.getElementById('ldDesignerConfig');
        if (configEl && configEl.textContent) {
            try {
                config = JSON.parse(configEl.textContent);
            } catch (parseErr) {
                console.error('LIFF Designer config parse failed:', parseErr);
                showToast('デザイナー設定の読み込みに失敗しました。ページを再読み込みしてください。', true);
                return;
            }
        }

        var designer;
        try {
            designer = new LiffDesigner(config);
            window.__ldDesigner = designer;
        } catch (err) {
            console.error('LIFF Designer init failed:', err);
            showToast('デザイナーの読み込みに失敗しました。ページを再読み込みしてください。', true);
            return;
        }

        try {
            if (designer.bindPanelDrawers) designer.bindPanelDrawers();
        } catch (err) {
            console.warn('panel drawers init failed', err);
        }
        try {
            if (designer.bindPanelResizer) designer.bindPanelResizer();
        } catch (err) {
            console.warn('panel resizer init failed', err);
        }

        // オンボーディング（初回表示 or 全タブ空の場合）
        if (designer.shouldShowOnboarding && designer.shouldShowOnboarding()) {
            setTimeout(function() {
                try { designer.showOnboarding(); } catch (e) { console.warn('onboarding skipped', e); }
            }, 300);
        }

        // ヘルプボタン
        var helpBtn = document.getElementById('helpBtn');
        if (helpBtn) {
            helpBtn.addEventListener('click', function() {
                if (designer.showHelpOverlay) designer.showHelpOverlay();
            });
        }

        // LIFF設定折りたたみ
        var liffSettingsToggle = document.getElementById('liffSettingsToggle');
        if (liffSettingsToggle) {
            liffSettingsToggle.addEventListener('click', function(e) {
                if (e.target.closest('input') || e.target.closest('textarea') || e.target.closest('select')) return;
                this.classList.toggle('is-open');
            });
        }

        // 保存フィードバック
        var form = document.getElementById('liffDesignerForm');
        if (form) {
            form.addEventListener('submit', function() {
                setTimeout(function() {
                    showToast('デザインを保存しました');
                }, 100);
            });
        }
    });

    function showToast(msg, isError, durationMs) {
        var el = document.getElementById('ldToast');
        if (!el) return;
        var dur = (typeof durationMs === 'number' && durationMs > 0) ? durationMs : 3000;
        el.innerHTML = '<i class="fas ' + (isError ? 'fa-exclamation-circle' : 'fa-check-circle') + '"></i> ' + msg;
        el.className = 'ld-toast' + (isError ? ' ld-toast--error' : ' ld-toast--success');
        requestAnimationFrame(function() { el.classList.add('ld-toast--visible'); });
        clearTimeout(window.__ldToastTimer);
        window.__ldToastTimer = setTimeout(function() { el.classList.remove('ld-toast--visible'); }, dur);
    }
    window.__ldShowToast = showToast;
</script>
@endsection
