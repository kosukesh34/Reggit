@extends('admin.layout')

@section('title', 'デザイン・ホーム')
@section('page-title', 'デザイン・ホーム')

@push('styles')
<style>
.home-settings { max-width: 920px; }
.design-tabs { display:flex; gap:6px; overflow-x:auto; padding:6px; margin-bottom:20px; border:1px solid #e7e5e4; border-radius:14px; background:#f5f5f4; }
.design-tabs button { flex:1; white-space:nowrap; border:0; border-radius:10px; padding:12px 16px; color:#57534e; background:transparent; font-weight:600; cursor:pointer; }
.design-tabs button[aria-selected="true"] { color:#173f35; background:white; box-shadow:0 1px 4px #00000012; }
[data-design-panel][hidden] { display:none; }
.chrome-color-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.chrome-color-grid .form-group { margin:0; }
@media(max-width:540px) { .chrome-color-grid { grid-template-columns:1fr; } }
.home-settings .card { margin-bottom: 16px; }
.home-settings .card-header { padding: 14px 18px; }
.home-settings .card-body { padding: 18px; }
.home-settings .card-title { font-size: 15px; margin: 0; }
.home-settings .section-help { margin: 0 0 12px; color: #78716c; font-size: 13px; }
.home-settings .form-group { margin-bottom: 16px; }
.home-settings .form-group:last-child { margin-bottom: 0; }
.home-sort-list { display: grid; gap: 8px; }
.home-sort-row {
    display: grid;
    grid-template-columns: 28px auto 1fr;
    gap: 10px;
    align-items: center;
    padding: 10px 12px;
    border: 1px solid #e7e5e4;
    border-radius: 10px;
    background: #fff;
}
.home-sort-row.is-off { background: #fafaf9; opacity: .72; }
.home-sort-row.is-dragging { opacity: .45; }
.home-sort-row--menu { grid-template-columns: 28px auto minmax(120px, 1.2fr) minmax(110px, .8fr); }
.home-sort-handle {
    cursor: grab;
    color: #a8a29e;
    text-align: center;
    user-select: none;
    touch-action: none;
}
.home-sort-handle:active { cursor: grabbing; }
.home-sort-row .checkbox-label { margin: 0; white-space: nowrap; }
.home-sort-row .home-sort-label { font-size: 14px; font-weight: 600; color: #292524; }
.home-color-row { display: flex; align-items: center; gap: 10px; max-width: 280px; }
.home-color-row .color-input { width: 42px; height: 38px; padding: 2px; }
.home-image-list { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 12px; }
.home-image-item { width: 112px; }
.home-image-item img { display: block; width: 112px; height: 112px; object-fit: cover; border-radius: 8px; }
.home-image-item label { display: flex; align-items: center; gap: 5px; margin-top: 5px; font-size: 12px; }
.home-legal-box {
    margin-top: 14px;
    padding: 12px 14px;
    border: 1px solid #e7e5e4;
    border-radius: 10px;
    background: #fafaf9;
    display: grid;
    gap: 10px;
}
.home-legal-box .form-label { margin-bottom: 4px; font-size: 12px; color: #78716c; }
.home-legal-box code { display: block; word-break: break-all; font-size: 12px; }
.home-settings-actions {
    position: sticky;
    bottom: 0;
    z-index: 5;
    display: flex;
    gap: 8px;
    padding: 12px 0 4px;
    background: linear-gradient(180deg, rgba(250,250,249,0), #fafaf9 28%);
}
@media (max-width: 720px) {
    .home-sort-row--menu { grid-template-columns: 28px auto 1fr; }
    .home-sort-row--menu .home-sort-icon { grid-column: 3; }
}
</style>
@endpush

@section('content')
@php
    $reorderChoices = static function (array $choices, ?array $selectedKeys): array {
        $selectedKeys = is_array($selectedKeys) ? $selectedKeys : [];
        $byKey = collect($choices)->keyBy('key');
        $ordered = [];
        foreach ($selectedKeys as $key) {
            if (! is_string($key) || ! $byKey->has($key)) {
                continue;
            }
            $item = $byKey->pull($key);
            $item['selected'] = true;
            $ordered[] = $item;
        }
        foreach ($byKey as $item) {
            $item['selected'] = false;
            $ordered[] = $item;
        }

        return $ordered;
    };

    $selectedHeader = old('native_navigation.header');
    $selectedFooter = old('native_navigation.footer');
    $selectedHamburger = old('native_navigation.hamburger');
    if (is_array($selectedHeader)) {
        $headerNavChoices = $reorderChoices($headerNavChoices ?? [], $selectedHeader);
    } else {
        $selectedHeader = array_column(array_filter($headerNavChoices ?? [], fn ($i) => ($i['selected'] ?? false) === true), 'key');
    }
    if (is_array($selectedFooter)) {
        $footerNavChoices = $reorderChoices($footerNavChoices ?? [], $selectedFooter);
    } else {
        $selectedFooter = array_column(array_filter($footerNavChoices ?? [], fn ($i) => ($i['selected'] ?? false) === true), 'key');
    }
    if (is_array($selectedHamburger)) {
        $hamburgerNavChoices = $reorderChoices($hamburgerNavChoices ?? [], $selectedHamburger);
    } else {
        $selectedHamburger = array_column(array_filter($hamburgerNavChoices ?? [], fn ($i) => ($i['selected'] ?? false) === true), 'key');
    }
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>デザイン・ホーム</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-palette"></i> デザイン・ホーム</h1>
</div>


<form action="{{ admin_route('admin.settings.home.update') }}" method="POST" enctype="multipart/form-data" id="home-settings-form" class="home-settings">
    @csrf
<input type="hidden" name="design_tab" id="design-active-tab" value="home">
<input type="hidden" name="native_navigation_present" value="1">
<p class="section-help">編集する画面を選んでください。各タブの変更は最後に「設定を保存」でまとめて保存します。</p>
<div class="design-tabs" role="tablist" aria-label="デザイン設定"><button type="button" role="tab" id="tab-home" aria-controls="panel-home" aria-selected="false" data-design-tab="home">ホーム</button><button type="button" role="tab" id="tab-header" aria-controls="panel-header" aria-selected="false" data-design-tab="header">ヘッダー</button><button type="button" role="tab" id="tab-footer" aria-controls="panel-footer" aria-selected="false" data-design-tab="footer">フッター</button><button type="button" role="tab" id="tab-menu" aria-controls="panel-menu" aria-selected="false" data-design-tab="menu">メニュー</button><button type="button" role="tab" id="tab-products" aria-controls="panel-products" aria-selected="false" data-design-tab="products">商品一覧</button><button type="button" role="tab" id="tab-buttons" aria-controls="panel-buttons" aria-selected="false" data-design-tab="buttons">ボタン</button></div><section role="tabpanel" id="panel-home" aria-labelledby="tab-home" data-design-panel="home">    <div class="card">
        <div class="card-header"><h2 class="card-title">ホーム画面</h2></div>
        <div class="card-body">
            <input type="hidden" name="home_show_member_card" value="0">
            <label class="checkbox-label"><input type="checkbox" name="home_show_member_card" value="1" {{ old('home_show_member_card', $homeShowMemberCard) ? 'checked' : '' }}> ホームに会員証を表示する</label>
            <p class="section-help">Skipアプリでホーム画像とメニューの間に会員コード（バーコード部分）を表示します。コードをタップすると提示用のシートが開きます。未登録のお客様には表示されません。</p>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="home_images">ホーム画像</label>
                <p class="section-help">最大10枚。複数枚は登録順にスライド表示されます。推奨 1200×1200px、JPEG・PNG・WebP、各8MB以下。</p>
                <input type="hidden" name="home_images_present" value="1">
                @if(!empty($homeImageUrls))
                    <div class="home-image-list">
                        @foreach($homeImageUrls as $index => $url)
                            <div class="home-image-item">
                                <img src="{{ $url }}" alt="ホーム画像 {{ $index + 1 }}">
                                <label><input type="checkbox" name="home_image_remove[]" value="{{ $index }}" {{ in_array($index, array_map('intval', old('home_image_remove', [])), true) ? 'checked' : '' }}> 削除</label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" id="home_images" name="home_images[]" accept="image/png,image/jpeg,image/webp" multiple>
                @error('home_images')<p class="text-danger">{{ $message }}</p>@enderror
                @error('home_images.*')<p class="text-danger">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="home_menu_layout" class="form-label">メニューのレイアウト</label>
                <select id="home_menu_layout" name="home_menu_layout" class="form-control" style="max-width:360px" required>
                    <option value="grid_3x2" {{ old('home_menu_layout', $homeMenuLayout ?? 'grid_3x2') === 'grid_3x2' ? 'selected' : '' }}>3列</option>
                    <option value="grid_2_columns" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'grid_2_columns' ? 'selected' : '' }}>2列</option>
                    <option value="list" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'list' ? 'selected' : '' }}>説明付きリスト</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ホームに出す項目</label>
                <p class="section-help">表示にチェックし、ドラッグで順番を変えられます。Skipでは有効な項目をすべて表示します。</p>
                <div class="home-sort-list home-settings-tbody">
                    @foreach($tabs as $index => $tab)
                        <div class="home-sort-row home-sort-row--menu home-settings-row {{ $tab['enabled'] ? '' : 'is-off' }}" data-index="{{ $index }}" draggable="true">
                            <span class="home-sort-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                            <label class="checkbox-label">
                                <input type="hidden" name="tabs[{{ $index }}][tab]" value="{{ $tab['tab'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][order]" class="home-settings-order-input" value="{{ (int) $tab['order'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][enabled]" value="0">
                                <input type="checkbox" name="tabs[{{ $index }}][enabled]" value="1" {{ $tab['enabled'] ? 'checked' : '' }} class="home-settings-enabled-cb">
                                <span>表示</span>
                            </label>
                            <input type="text" class="form-control" name="tabs[{{ $index }}][label]" value="{{ old("tabs.{$index}.label", $tab['label']) }}" placeholder="表示名">
                            <select class="form-control home-sort-icon" name="tabs[{{ $index }}][icon]">
                                @foreach($availableIcons ?? [] as $icon)
                                    <option value="{{ $icon }}" {{ ($tab['icon'] ?? '') === $icon ? 'selected' : '' }}>{{ $icon }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</section>
<section role="tabpanel" id="panel-header" aria-labelledby="tab-header" data-design-panel="header">    <div class="card">
        <div class="card-header"><h2 class="card-title">ヘッダー</h2></div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'navigation_header_image',
                'logoClearName' => 'navigation_header_image_clear',
                'logoFieldId' => 'navigation_header_image',
                'logoCurrentUrl' => (string) old('navigation_header_image_url', $navigationHeaderImageUrl ?? ''),
                'logoLabel' => '中央の画像',
                'logoHelp' => '推奨 600×120px。未設定時は店舗名を表示します。',
            ])
            <div class="form-group">
                <label for="navigation_header_background_color" class="form-label">背景色</label>
                <div class="home-color-row">
                    <input type="color" id="navigation_header_background_color_picker" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" class="color-input">
                    <input type="text" class="form-control" id="navigation_header_background_color" name="navigation_header_background_color" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">メニュー（最大1件）</label>
                <p class="section-help">チェックした項目がヘッダーに出ます。ドラッグで順番を変えられます。</p>
                <div class="home-sort-list" data-nav-placement="header" data-nav-limit="1">
                    @foreach($headerNavChoices as $item)
                        @php($checked = in_array($item['key'], $selectedHeader, true))
                        <div class="home-sort-row {{ $checked ? '' : 'is-off' }}" draggable="true">
                            <span class="home-sort-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                            <label class="checkbox-label">
                                <input type="checkbox" name="native_navigation[header][]" value="{{ $item['key'] }}" {{ $checked ? 'checked' : '' }}>
                                <span>表示</span>
                            </label>
                            <span class="home-sort-label">{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
<div class="card-body"><h3 class="card-title" style="margin-bottom:16px">ボタンと背景の色</h3><div class="chrome-color-grid"><div class="form-group"><label for="chrome-header_icon" class="form-label">ヘッダーボタンの文字・アイコン</label><input id="chrome-header_icon" type="color" name="native_chrome_colors[header_icon]" value="{{ old('native_chrome_colors.header_icon', $nativeChromeColors['header_icon'] ?? '#292524') }}" style="width:64px;height:44px"></div></div></div>    </div>

</section>
<section role="tabpanel" id="panel-footer" aria-labelledby="tab-footer" data-design-panel="footer">    <div class="card">
        <div class="card-header"><h2 class="card-title">フッター</h2></div>
        <div class="card-body">
            <p class="section-help">画面下部のタブバーです。最大5件。ドラッグで順番を変えられます。</p>
            <div class="home-sort-list" data-nav-placement="footer" data-nav-limit="5">
                @foreach($footerNavChoices as $item)
                    @php($checked = in_array($item['key'], $selectedFooter, true))
                    <div class="home-sort-row {{ $checked ? '' : 'is-off' }}" draggable="true">
                        <span class="home-sort-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                        <label class="checkbox-label">
                            <input type="checkbox" name="native_navigation[footer][]" value="{{ $item['key'] }}" {{ $checked ? 'checked' : '' }}>
                            <span>表示</span>
                        </label>
                        <span class="home-sort-label">{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
<div class="card-body"><h3 class="card-title" style="margin-bottom:16px">ボタンと背景の色</h3><div class="chrome-color-grid"><div class="form-group"><label for="chrome-footer_background" class="form-label">フッター全体の背景</label><input id="chrome-footer_background" type="color" name="native_chrome_colors[footer_background]" value="{{ old('native_chrome_colors.footer_background', $nativeChromeColors['footer_background'] ?? '#FFFFFF') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_icon" class="form-label">フッターボタンの文字・アイコン</label><input id="chrome-footer_icon" type="color" name="native_chrome_colors[footer_icon]" value="{{ old('native_chrome_colors.footer_icon', $nativeChromeColors['footer_icon'] ?? '#78716C') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_active_icon" class="form-label">フッター選択中の文字・アイコン</label><input id="chrome-footer_active_icon" type="color" name="native_chrome_colors[footer_active_icon]" value="{{ old('native_chrome_colors.footer_active_icon', $nativeChromeColors['footer_active_icon'] ?? '#173F35') }}" style="width:64px;height:44px"></div><div class="form-group"><label for="chrome-footer_active_background" class="form-label">フッター選択中の背景</label><input id="chrome-footer_active_background" type="color" name="native_chrome_colors[footer_active_background]" value="{{ old('native_chrome_colors.footer_active_background', $nativeChromeColors['footer_active_background'] ?? '#EAF2EE') }}" style="width:64px;height:44px"></div></div></div>    </div>

</section>
<section role="tabpanel" id="panel-menu" aria-labelledby="tab-menu" data-design-panel="menu">    <div class="card">
        <div class="card-header"><h2 class="card-title">ハンバーガーメニュー</h2></div>
        <div class="card-body">
            <p class="section-help">☰ から開くメニューです。ドラッグで順番を変えられます。</p>
            <div class="home-sort-list" data-nav-placement="hamburger">
                @foreach($hamburgerNavChoices as $item)
                    @php($checked = in_array($item['key'], $selectedHamburger, true))
                    <div class="home-sort-row {{ $checked ? '' : 'is-off' }}" draggable="true">
                        <span class="home-sort-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                        <label class="checkbox-label">
                            <input type="checkbox" name="native_navigation[hamburger][]" value="{{ $item['key'] }}" {{ $checked ? 'checked' : '' }}>
                            <span>表示</span>
                        </label>
                        <span class="home-sort-label">{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="home-legal-box">
                <div>
                    <div class="form-label">この店舗の利用規約URL</div>
                    <code class="smaregi-url">{{ $termsUrl }}</code>
                </div>
                <div>
                    <div class="form-label">この店舗のプライバシーポリシーURL</div>
                    <code class="smaregi-url">{{ $privacyUrl }}</code>
                </div>
            </div>
        </div>
    </div>

</section>
<section role="tabpanel" id="panel-products" aria-labelledby="tab-products" data-design-panel="products">    <div class="card">
        <div class="card-header"><h2 class="card-title">商品一覧</h2></div>
        <div class="card-body">
            <div class="form-group">
                <label for="product_display_layout" class="form-label">表示レイアウト</label>
                <select id="product_display_layout" name="product_display_layout" class="form-control" style="max-width:360px" required>
                    <option value="list_with_image" {{ old('product_display_layout', $productDisplayLayout ?? 'list_with_image') === 'list_with_image' ? 'selected' : '' }}>縦リスト（画像付き）</option>
                    <option value="grid_2_columns" {{ old('product_display_layout', $productDisplayLayout ?? '') === 'grid_2_columns' ? 'selected' : '' }}>2列（画像付き）</option>
                    <option value="name_only" {{ old('product_display_layout', $productDisplayLayout ?? '') === 'name_only' ? 'selected' : '' }}>コンパクト（画像なし）</option>
                </select>
            </div>
        </div>
    </div>

</section>
<section role="tabpanel" id="panel-buttons" aria-labelledby="tab-buttons" data-design-panel="buttons"><div class="card"><div class="card-header"><h2 class="card-title">アプリ内のボタン</h2></div><div class="card-body"><p class="section-help">保存・予約・商品追加などの主要ボタンと、キャンセルなどの補助ボタンに反映します。メニューや会員ランクの色は変更しません。</p><div class="chrome-color-grid"><div class="form-group"><label class="form-label" for="chrome-primary_button_background">主要ボタンの背景</label><input id="chrome-primary_button_background" type="color" name="native_chrome_colors[primary_button_background]" value="{{ old('native_chrome_colors.primary_button_background', $nativeChromeColors['primary_button_background'] ?? '#173F35') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-primary_button_text">主要ボタンの文字・アイコン</label><input id="chrome-primary_button_text" type="color" name="native_chrome_colors[primary_button_text]" value="{{ old('native_chrome_colors.primary_button_text', $nativeChromeColors['primary_button_text'] ?? '#FFFFFF') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-secondary_button_background">補助ボタンの背景</label><input id="chrome-secondary_button_background" type="color" name="native_chrome_colors[secondary_button_background]" value="{{ old('native_chrome_colors.secondary_button_background', $nativeChromeColors['secondary_button_background'] ?? '#EDF1ED') }}" style="width:64px;height:44px"></div><div class="form-group"><label class="form-label" for="chrome-secondary_button_text">補助ボタンの文字・アイコン</label><input id="chrome-secondary_button_text" type="color" name="native_chrome_colors[secondary_button_text]" value="{{ old('native_chrome_colors.secondary_button_text', $nativeChromeColors['secondary_button_text'] ?? '#17211C') }}" style="width:64px;height:44px"></div></div></div></div></section>
    <div class="home-settings-actions form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 設定を保存</button>
    </div>
</form>

<script>
(function() {
    var tabButtons = Array.from(document.querySelectorAll('[data-design-tab]'));
    function selectDesignTab(key, focus) {
        if (!tabButtons.some(function(button) { return button.dataset.designTab === key; })) key = 'home';
        tabButtons.forEach(function(button) {
            var selected = button.dataset.designTab === key;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
            if (selected && focus) button.focus();
        });
        document.querySelectorAll('[data-design-panel]').forEach(function(panel) { panel.hidden = panel.dataset.designPanel !== key; });
        document.getElementById('design-active-tab').value = key;
        history.replaceState(null, '', '#design-' + key);
    }
    tabButtons.forEach(function(button, index) {
        button.addEventListener('click', function() { selectDesignTab(button.dataset.designTab, false); });
        button.addEventListener('keydown', function(event) {
            var next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabButtons.length;
            if (event.key === 'ArrowLeft') next = (index + tabButtons.length - 1) % tabButtons.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabButtons.length - 1;
            if (next !== undefined) { event.preventDefault(); selectDesignTab(tabButtons[next].dataset.designTab, true); }
        });
    });
    var initialTab = location.hash.replace('#design-', '') || @json(old('design_tab', 'home'));
    selectDesignTab(initialTab, false);
    document.getElementById('home-settings-form').addEventListener('invalid', function(event) {
        var panel = event.target.closest('[data-design-panel]');
        if (panel) selectDesignTab(panel.dataset.designPanel, false);
    }, true);

    function enableSortable(list, options) {
        if (!list) return;
        options = options || {};
        var dragged = null;
        var rowSelector = options.rowSelector || '.home-sort-row';

        list.addEventListener('dragstart', function(e) {
            var row = e.target.closest(rowSelector);
            if (!row || !list.contains(row)) return;
            dragged = row;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '1');
        });
        list.addEventListener('dragend', function() {
            if (dragged) {
                dragged.classList.remove('is-dragging');
                dragged = null;
            }
            if (typeof options.onReorder === 'function') options.onReorder();
        });
        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (!dragged) return;
            var row = e.target.closest(rowSelector);
            if (!row || row === dragged || !list.contains(row)) return;
            var rect = row.getBoundingClientRect();
            var next = e.clientY > rect.top + rect.height / 2 ? row.nextElementSibling : row;
            if (next && next !== dragged) list.insertBefore(dragged, next);
            else if (!next) list.appendChild(dragged);
        });
        list.addEventListener('drop', function(e) { e.preventDefault(); });
    }

    function syncHomeOrder(list) {
        list.querySelectorAll('.home-settings-row').forEach(function(row, i) {
            var input = row.querySelector('.home-settings-order-input');
            if (input) input.value = i;
        });
    }

    var homeList = document.querySelector('.home-settings-tbody');
    if (homeList) {
        enableSortable(homeList, {
            rowSelector: '.home-settings-row',
            onReorder: function() { syncHomeOrder(homeList); },
        });
        homeList.addEventListener('change', function(e) {
            if (!e.target.classList.contains('home-settings-enabled-cb')) return;
            var row = e.target.closest('.home-settings-row');
            if (row) row.classList.toggle('is-off', !e.target.checked);
        });
        syncHomeOrder(homeList);
    }

    document.querySelectorAll('.home-sort-list[data-nav-placement]').forEach(function(list) {
        enableSortable(list);
        var limit = parseInt(list.getAttribute('data-nav-limit') || '', 10);
        list.addEventListener('change', function(e) {
            if (e.target.type !== 'checkbox') return;
            var row = e.target.closest('.home-sort-row');
            if (row) row.classList.toggle('is-off', !e.target.checked);
            if (!limit) return;
            var checked = list.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length <= limit) return;
            e.target.checked = false;
            if (row) row.classList.add('is-off');
            alert('最大' + limit + '件まで選択できます。');
        });
    });

    var colorPicker = document.getElementById('navigation_header_background_color_picker');
    var colorText = document.getElementById('navigation_header_background_color');
    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', function() { colorText.value = colorPicker.value.toUpperCase(); });
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(colorText.value)) colorPicker.value = colorText.value;
        });
    }
})();
</script>
@endsection
