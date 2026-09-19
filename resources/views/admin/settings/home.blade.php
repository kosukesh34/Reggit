@extends('admin.layout')

@section('title', 'ホーム設定')
@section('page-title', 'ホーム設定')

@push('styles')
<style>
.home-settings-drag-handle { cursor: grab; color: var(--text-tertiary, #78716c); padding: 4px 8px; user-select: none; }
.home-settings-drag-handle:active { cursor: grabbing; }
.home-settings-tbody tr.home-settings-row--hidden { background: #f4f4f5 !important; opacity: 0.85; }
.home-settings-tbody tr.home-settings-row--hidden td { color: #9ca3af; }
.home-settings-tbody tr.home-settings-row--hidden .home-settings-drag-handle { color: #d1d5db; }
.home-settings-tbody tr.home-settings-row--dragging { opacity: 0.6; }
</style>
@endpush

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>ホーム設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-home"></i> ホーム設定</h1>
</div>

<form action="{{ admin_route('admin.settings.home.update') }}" method="POST" enctype="multipart/form-data" id="home-settings-form">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-window-maximize"></i> アプリのナビゲーションバー</h2>
        </div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'navigation_header_image',
                'logoClearName' => 'navigation_header_image_clear',
                'logoFieldId' => 'navigation_header_image',
                'logoCurrentUrl' => (string) old('navigation_header_image_url', $navigationHeaderImageUrl ?? ''),
                'logoLabel' => 'ナビゲーションバー中央の画像',
                'logoHelp' => '全タブ共通のナビゲーションバー中央に表示します。推奨サイズは 600 × 120px（比率5:1）、PNG・WebPの透過画像が適しています。',
            ])
            <div class="form-group mt-3">
                <label for="navigation_header_background_color" class="form-label">ヘッダー背景色</label>
                <div style="display:flex;align-items:center;gap:12px;max-width:360px">
                    <input type="color" id="navigation_header_background_color_picker" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" style="width:52px;height:42px;padding:3px">
                    <input type="text" class="form-control" id="navigation_header_background_color" name="navigation_header_background_color" value="{{ old('navigation_header_background_color', $navigationHeaderBackgroundColor ?? '#FFFFFF') }}" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
                <p class="form-text">ステータスバー直下から各タブの上部まで共通して使用する色です。</p>
            </div>
            <div class="form-group mt-3">
                <label class="form-label">ホームメニュー</label>
                <select name="home_menu_layout" class="form-control" style="max-width:360px" required>
                    <option value="grid_3x2" {{ old('home_menu_layout', $homeMenuLayout ?? 'grid_3x2') === 'grid_3x2' ? 'selected' : '' }}>横3列 × 縦2行</option>
                    <option value="grid_2_columns" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'grid_2_columns' ? 'selected' : '' }}>2列</option>
                    <option value="list" {{ old('home_menu_layout', $homeMenuLayout ?? '') === 'list' ? 'selected' : '' }}>説明付きリスト</option>
                </select>
                <p class="form-text">ホーム画面の画像下に表示するメニューのレイアウトを選択します。表示項目は下の一覧で設定できます。</p>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-image"></i> ホーム画面の画像</h2>
        </div>
        <div class="card-body">
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'home_image',
                'logoClearName' => 'home_image_clear',
                'logoFieldId' => 'home_image',
                'logoCurrentUrl' => (string) old('home_image_url', $homeImageUrl ?? ''),
                'logoLabel' => 'ホーム上部のメイン画像',
                'logoHelp' => '推奨サイズは 1200 × 660px（約20:11）です。JPEG・PNG・WebP、8MB以下の画像を設定できます。',
            ])
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-th-large"></i> ホームメニューの表示設定</h2>
        </div>
        <div class="card-body">
            <p class="form-text mb-3">ホームメニューに表示する項目へチェックを入れ、行をドラッグして並び順を変更できます。有効な項目のうち先頭6件を表示します。</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:44px" aria-label="並び替え"></th>
                            <th style="width:80px">表示</th>
                            <th style="width:90px">表示順</th>
                            <th style="width:100px">タブ（キー）</th>
                            <th>ラベル</th>
                            <th style="width:180px">アイコン</th>
                        </tr>
                    </thead>
                    <tbody class="home-settings-tbody">
                        @foreach($tabs as $index => $tab)
                        <tr class="home-settings-row {{ $tab['enabled'] ? '' : 'home-settings-row--hidden' }}" data-index="{{ $index }}" draggable="true">
                            <td class="home-settings-drag-cell">
                                <span class="home-settings-drag-handle" title="ドラッグして並び替え"><i class="fas fa-grip-vertical"></i></span>
                            </td>
                            <td>
                                <input type="hidden" name="tabs[{{ $index }}][tab]" value="{{ $tab['tab'] }}">
                                <label class="checkbox-label">
                                    <input type="hidden" name="tabs[{{ $index }}][enabled]" value="0">
                                    <input type="checkbox" name="tabs[{{ $index }}][enabled]" value="1" {{ $tab['enabled'] ? 'checked' : '' }} class="home-settings-enabled-cb">
                                    <span>表示</span>
                                </label>
                            </td>
                            <td>
                                <select class="form-control home-settings-order-input" name="tabs[{{ $index }}][order]">
                                    @for($position = 0; $position < count($tabs); $position++)
                                        <option value="{{ $position }}" {{ (int) $tab['order'] === $position ? 'selected' : '' }}>{{ $position + 1 }}</option>
                                    @endfor
                                </select>
                            </td>
                            <td>
                                <span class="text-muted">{{ $tab['tab'] }}</span>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="tabs[{{ $index }}][label]" value="{{ old("tabs.{$index}.label", $tab['label']) }}" placeholder="表示名">
                            </td>
                            <td>
                                <select class="form-control" name="tabs[{{ $index }}][icon]">
                                    @foreach($availableIcons ?? [] as $icon)
                                        <option value="{{ $icon }}" {{ ($tab['icon'] ?? '') === $icon ? 'selected' : '' }}>{{ $icon }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存する</button>
                <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">基本設定に戻る</a>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    var tbody = document.querySelector('.home-settings-tbody');
    if (!tbody) return;

    function syncOrderInputs() {
        var rows = tbody.querySelectorAll('tr.home-settings-row');
        rows.forEach(function(row, i) {
            var input = row.querySelector('.home-settings-order-input');
            if (input) input.value = i;
        });
    }

    function toggleRowHidden(row, enabled) {
        if (enabled) row.classList.remove('home-settings-row--hidden');
        else row.classList.add('home-settings-row--hidden');
    }

    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('home-settings-enabled-cb')) {
            var tr = e.target.closest('tr');
            if (tr) toggleRowHidden(tr, e.target.checked);
        }
    });

    var dragged = null;
    tbody.addEventListener('dragstart', function(e) {
        var row = e.target.closest('tr');
        if (!row || !row.classList.contains('home-settings-row')) return;
        dragged = row;
        row.classList.add('home-settings-row--dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.dataset.index || '');
        e.dataTransfer.setDragImage(row, 0, 0);
    });
    tbody.addEventListener('dragend', function(e) {
        if (dragged) {
            dragged.classList.remove('home-settings-row--dragging');
            dragged = null;
        }
        syncOrderInputs();
    });
    tbody.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var row = e.target.closest('tr');
        if (!row || row === dragged || !row.classList.contains('home-settings-row')) return;
        var rect = row.getBoundingClientRect();
        var mid = rect.top + rect.height / 2;
        var next = e.clientY > mid ? row.nextElementSibling : row;
        if (next && next.classList.contains('home-settings-row') && next !== dragged) {
            tbody.insertBefore(dragged, next);
        } else if (!next) {
            tbody.appendChild(dragged);
        }
    });
    tbody.addEventListener('drop', function(e) {
        e.preventDefault();
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
