@extends('admin.layout')

@section('title', 'アプリデザイン')
@section('page-title', 'アプリデザイン')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/coupons.css') }}">
<style>
.home-settings { max-width: 1250px; }
[data-design-panel][hidden] { display:none; }
.web-page-editor[hidden], .home-sort-row[hidden] { display: none; }
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
.home-sort-row[hidden] { display: none; }
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
.home-editor-layout { display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:24px; align-items:start; }
.home-preview-panel { position:sticky; top:20px; }
.home-preview-panel h2 { font-size:16px; }
.home-gallery-action { display:grid; gap:8px; padding:12px; border:1px solid #e7e5e4; border-radius:12px; margin-bottom:12px; }
.home-gallery-action img { width:80px; max-height:80px; object-fit:cover; border-radius:8px; }
.home-preview-news { background:#fff; padding:14px; border-radius:12px; }
@media(max-width:1000px) { .home-editor-layout { grid-template-columns:minmax(0,1fr); } .home-preview-panel { position:static; width:min(100%,330px); margin:auto; } }
</style>
@endpush

@section('content')
@php
    $homePreviewBase = route('liff.project', ['project_key' => request()->route('project_key')], false);
    $homePreviewUrl = $homePreviewBase ? $homePreviewBase.(str_contains($homePreviewBase, '?') ? '&' : '?').'designer_live_preview=1&designer_embed=1&designer_source=member&home_form_preview=1#/home' : null;

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

@include('admin.settings._app-design-navigation', ['activeDesign' => 'layout'])




<form action="{{ admin_route('admin.settings.home.update') }}" method="POST" enctype="multipart/form-data" id="home-settings-form" class="home-settings">
    @csrf
<input type="hidden" name="design_tab" id="design-active-tab" value="home">
<input type="hidden" name="native_navigation_present" value="1">
<p class="section-help">編集する画面を選んでください。各タブの変更は最後に「設定を保存」でまとめて保存します。</p>
<div class="settings-category-tabs" role="tablist" aria-label="デザイン設定"><button type="button" role="tab" id="tab-home" aria-controls="panel-home" aria-selected="false" data-design-tab="home">ホーム</button><button type="button" role="tab" id="tab-header" aria-controls="panel-header" aria-selected="false" data-design-tab="header">ヘッダー</button><button type="button" role="tab" id="tab-footer" aria-controls="panel-footer" aria-selected="false" data-design-tab="footer">フッター</button><button type="button" role="tab" id="tab-menu" aria-controls="panel-menu" aria-selected="false" data-design-tab="menu">メニュー</button><button type="button" role="tab" id="tab-products" aria-controls="panel-products" aria-selected="false" data-design-tab="products">商品一覧</button></div><section role="tabpanel" id="panel-home" aria-labelledby="tab-home" data-design-panel="home"><div class="home-editor-layout"><div>
@include('admin.settings._home-content')
<div class="card">
        <div class="card-header"><h2 class="card-title">ホーム画面</h2></div>
        <div class="card-body">
            <input type="hidden" name="home_show_member_card" value="0">
            <label class="checkbox-label"><input type="checkbox" name="home_show_member_card" value="1" {{ old('home_show_member_card', $homeShowMemberCard) ? 'checked' : '' }}> ホームに会員証を表示する</label>
            <p class="section-help">LINE・Skipのホームに会員コード（バーコード部分）を表示します。コードをタップすると提示用のシートが開きます。未登録のお客様には表示されません。</p>
        </div>
        <div class="card-body">
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
                        @php($isWebPage = in_array($tab['tab'], \App\Support\LiffDesignerSanitizer::LINK_MENU_TABS, true))
                        <div class="home-sort-row home-sort-row--menu home-settings-row {{ $tab['enabled'] ? '' : 'is-off' }}" data-index="{{ $index }}" @if($isWebPage) data-home-web-page="{{ $tab['tab'] }}" @endif draggable="true">
                            <span class="home-sort-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                            <label class="checkbox-label">
                                <input type="hidden" name="tabs[{{ $index }}][tab]" value="{{ $tab['tab'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][order]" class="home-settings-order-input" value="{{ (int) $tab['order'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][enabled]" value="0">
                                <input type="checkbox" name="tabs[{{ $index }}][enabled]" value="1" {{ $tab['enabled'] ? 'checked' : '' }} class="home-settings-enabled-cb">
                                <span>表示</span>
                            </label>
                            @if($isWebPage)
                                <span class="home-sort-label">{{ old("tabs.{$index}.label", $tab['label']) }}</span>
                            @else
                            <input type="text" class="form-control" name="tabs[{{ $index }}][label]" value="{{ old("tabs.{$index}.label", $tab['label']) }}" placeholder="表示名">
                            @endif
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

</div><aside class="home-preview-panel"><h2>ホームプレビュー</h2><p class="section-help">実際の店舗アプリを表示します。変更は保存するまで公開されません。</p>

<div class="cp-real-preview-stage">
@if($homePreviewUrl)
<div class="cp-device-shell">
<span class="cp-device-button cp-device-button--silent" aria-hidden="true"></span><span class="cp-device-button cp-device-button--volume-up" aria-hidden="true"></span><span class="cp-device-button cp-device-button--volume-down" aria-hidden="true"></span><span class="cp-device-button cp-device-button--power" aria-hidden="true"></span>
<div class="cp-real-preview-frame"><div class="cp-real-preview-screen"><iframe id="home-live-preview" title="実際の店舗アプリのホーム" src="{{ $homePreviewUrl }}"></iframe></div></div>
</div>
@else
<p>店舗アプリのURLを取得できませんでした。</p>
@endif
</div></aside></div>
</section>
<section role="tabpanel" id="panel-header" aria-labelledby="tab-header" data-design-panel="header">    <div class="card">
        <div class="card-header"><h2 class="card-title">ヘッダー</h2></div>
        <div class="card-body">

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
    </div>

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
    </div>

</section>
<section role="tabpanel" id="panel-menu" aria-labelledby="tab-menu" data-design-panel="menu">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Webページ</h2></div>
        <div class="card-body">
            <p class="section-help">＋ボタンでWebページを追加し、表示名・URL・開き方を設定してください。下のメニュー一覧で「表示」を選択できます。ホーム・ヘッダー・フッターにも、各タブの「表示」で個別に配置できます。削除ボタンでページを削除できます。最大3件まで追加できます。</p>
            <p class="section-help">https のURLに対応しています。内部ブラウザはSkipアプリ内で表示し、外部ブラウザは端末の標準ブラウザで開きます。</p>
            @foreach($tabs as $index => $tab)
                @if(in_array($tab['tab'], \App\Support\LiffDesignerSanitizer::LINK_MENU_TABS, true))
                    <div class="form-group home-legal-box web-page-editor" data-web-page="{{ $tab['tab'] }}" {{ old("tabs.{$index}.url", $tab['url'] ?? '') === '' ? 'hidden' : '' }}>
                        <label class="form-label" for="webview-label-{{ $index }}">表示名</label>
                        <input type="text" class="form-control" id="webview-label-{{ $index }}" name="tabs[{{ $index }}][label]" value="{{ old("tabs.{$index}.label", $tab['label']) }}" maxlength="255">
                        <label class="form-label" for="webview-url-{{ $index }}">{{ $tab['label'] }} のURL</label>
                        <input type="url" class="form-control" id="webview-url-{{ $index }}" name="tabs[{{ $index }}][url]" value="{{ old("tabs.{$index}.url", $tab['url'] ?? '') }}" placeholder="https://example.com" maxlength="1000">
                        @error("tabs.{$index}.url")<div class="text-danger">{{ $message }}</div>@enderror
                        <label class="form-label" for="webview-open-{{ $index }}">開き方</label>
                        <select class="form-control" id="webview-open-{{ $index }}" name="tabs[{{ $index }}][open]">
                            <option value="embed" {{ old("tabs.{$index}.open", $tab['open']) === 'embed' ? 'selected' : '' }}>内部ブラウザ（アプリ内で表示）</option>
                            <option value="external" {{ old("tabs.{$index}.open", $tab['open']) === 'external' ? 'selected' : '' }}>外部ブラウザ</option>
                        </select>
                        <button type="button" class="btn btn-secondary web-page-remove">削除</button>
                    </div>
                @endif
            @endforeach
            <button type="button" class="btn btn-secondary" id="web-page-add">＋ Webページを追加</button>
        </div>
    </div>
    <div class="card">
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

    var webPages = Array.from(document.querySelectorAll('[data-web-page]'));
    var addWebPage = document.getElementById('web-page-add');
    function syncWebPages() {
        addWebPage.disabled = webPages.every(function(page) { return !page.hidden; });
        webPages.forEach(function(page) {
            page.querySelector('input[name$="[url]"]').required = !page.hidden;
            var key = page.dataset.webPage;
            var label = page.querySelector('input[name$="[label]"]').value;
            var homeRow = document.querySelector('[data-home-web-page="' + key + '"]');
            homeRow.hidden = page.hidden;
            homeRow.querySelector('.home-sort-label').textContent = label;
            if (page.hidden) homeRow.querySelector('.home-settings-enabled-cb').checked = false;
            homeRow.classList.toggle('is-off', !homeRow.querySelector('.home-settings-enabled-cb').checked);
            document.querySelectorAll('.home-sort-list[data-nav-placement] input[type="checkbox"]').forEach(function(input) {
                if (input.value !== key) return;
                var row = input.closest('.home-sort-row');
                row.hidden = page.hidden;
                row.querySelector('.home-sort-label').textContent = label;
                if (page.hidden) input.checked = false;
            });
        });
    }
    addWebPage.addEventListener('click', function() {
        var page = webPages.find(function(page) { return page.hidden; });
        if (!page) return;
        page.hidden = false;
        document.querySelector('[data-home-web-page="' + page.dataset.webPage + '"] .home-settings-enabled-cb').checked = true;
        syncWebPages();
        page.querySelector('input[name$="[label]"]').focus();
    });
    webPages.forEach(function(page) {
        page.addEventListener('input', syncWebPages);
        page.querySelector('.web-page-remove').addEventListener('click', function() {
            page.hidden = true;
            page.querySelector('input[name$="[url]"]').value = '';
            syncWebPages();
        });
    });
    syncWebPages();

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

})();
</script>
@endsection
