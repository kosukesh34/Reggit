@extends('admin.layout')

@section('title', 'ホーム設定')
@section('page-title', 'ホーム設定')

@push('styles')
<style>
.home-settings { max-width: 760px; }
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
    <span>ホーム設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-home"></i> ホーム設定</h1>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<form action="{{ admin_route('admin.settings.home.update') }}" method="POST" enctype="multipart/form-data" id="home-settings-form" class="home-settings">
    @csrf
    <input type="hidden" name="native_navigation_present" value="1">

    <div class="card">
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
                <label class="form-label">メニュー（最大2件）</label>
                <p class="section-help">チェックした項目がヘッダーに出ます。ドラッグで順番を変えられます。</p>
                <div class="home-sort-list" data-nav-placement="header" data-nav-limit="2">
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

    <div class="card">
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

    <div class="card">
        <div class="card-header"><h2 class="card-title">ホーム画面</h2></div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'home_image',
                'logoClearName' => 'home_image_clear',
                'logoFieldId' => 'home_image',
                'logoCurrentUrl' => (string) old('home_image_url', $homeImageUrl ?? ''),
                'logoLabel' => 'メイン画像',
                'logoHelp' => '推奨 1200×660px。JPEG・PNG・WebP、8MB以下。',
            ])
            <div class="form-group">
                <label for="home_menu_layout" class="form-label">メニューのレイアウト</label>
                <select id="home_menu_layout" name="home_menu_layout" class="form-control" style="max-width:360px" required>
                    <option value="grid_3x2" {{ old('home_menu_layout', $homeMenuLayout ?? 'grid_3x2') === 'grid_3x2' ? 'selected' : '' }}>横3列 × 縦2行</option>
                    <option value="grid_2_columns" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'grid_2_columns' ? 'selected' : '' }}>2列</option>
                    <option value="list" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'list' ? 'selected' : '' }}>説明付きリスト</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ホームに出す項目</label>
                <p class="section-help">表示にチェックし、ドラッグで順番を変えられます。先頭6件まで表示されます。</p>
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

    <div class="card">
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

    <div class="home-settings-actions form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存する</button>
        <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">基本設定に戻る</a>
    </div>
</form>

<script>
(function() {
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
