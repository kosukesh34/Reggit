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
    <p class="page-header-desc">LINE画面（LIFF）で表示するタブの表示ON/OFF・並び順・ラベル・アイコンを一括で設定できます。</p>
</div>
@include('admin.settings._context', ['pageKey' => 'home'])

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> {{ session('info') }}</div>
@endif

<form action="{{ admin_route('admin.settings.home.update') }}" method="POST" id="home-settings-form">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-th-large"></i> 表示するタブ</h2>
        </div>
        <div class="card-body">
            <p class="form-text mb-3">表示するタブにチェックを入れ、行をドラッグして並び順を変更できます。ラベルとアイコンもここで編集できます。表示していないタブは灰色で表示されます。</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:44px" aria-label="並び替え"></th>
                            <th style="width:80px">表示</th>
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
                                <input type="hidden" class="home-settings-order-input" name="tabs[{{ $index }}][order]" value="{{ $tab['order'] }}">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="tabs[{{ $index }}][enabled]" value="1" {{ $tab['enabled'] ? 'checked' : '' }} class="home-settings-enabled-cb">
                                    <span>表示</span>
                                </label>
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
})();
</script>
@endsection
