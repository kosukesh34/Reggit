@extends('admin.layout')

@section('title', 'メニュー設定')
@section('page-title', 'メニュー設定')

@section('content')
@php
    $menuItems = $settings['menuItems'] ?? [];
    $availableTabs = $settings['availableTabs'] ?? [];
    $availableIcons = $settings['availableIcons'] ?? [];
@endphp

<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>メニュー設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-bars"></i> メニュー設定</h1>
</div>


<form action="{{ admin_route('admin.settings.menu.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-list"></i> メニュー項目</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>表示</th>
                            <th>タブ</th>
                            <th>ラベル</th>
                            <th>アイコン</th>
                            <th>並び順</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menuItems as $index => $item)
                        @php
                            $enabled = $item['enabled'] ?? true;
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="menu_items[{{ $index }}][enabled]" value="1" {{ $enabled ? 'checked' : '' }}>
                            </td>
                            <td>
                                <select class="form-control" name="menu_items[{{ $index }}][tab]">
                                    @foreach($availableTabs as $tab)
                                        <option value="{{ $tab['tab'] }}" {{ ($item['tab'] ?? '') === $tab['tab'] ? 'selected' : '' }}>
                                            {{ $tab['label'] }} ({{ $tab['tab'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="menu_items[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}">
                            </td>
                            <td>
                                <select class="form-control" name="menu_items[{{ $index }}][icon]">
                                    @foreach($availableIcons as $icon)
                                        <option value="{{ $icon }}" {{ ($item['icon'] ?? '') === $icon ? 'selected' : '' }}>
                                            {{ $icon }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control" name="menu_items[{{ $index }}][order]" value="{{ $item['order'] ?? $index }}" min="0">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeMenuRow(this)"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-group mt-12">
                <button type="button" class="btn btn-sm btn-secondary" onclick="addMenuRow()"><i class="fas fa-plus"></i> メニュー項目を追加</button>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>
</form>
<script>
const availableTabs = @json($availableTabs);
const availableIcons = @json($availableIcons);

function buildTabOptions(selected) {
    return availableTabs.map(tab => {
        const isSelected = selected === tab.tab ? 'selected' : '';
        return `<option value="${tab.tab}" ${isSelected}>${tab.label} (${tab.tab})</option>`;
    }).join('');
}

function buildIconOptions(selected) {
    return availableIcons.map(icon => {
        const isSelected = selected === icon ? 'selected' : '';
        return `<option value="${icon}" ${isSelected}>${icon}</option>`;
    }).join('');
}

function addMenuRow() {
    const tbody = document.querySelector('table tbody');
    const index = tbody.children.length;
    const defaultTab = availableTabs[0]?.tab || '';
    const defaultIcon = availableIcons[0] || '';
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="checkbox" name="menu_items[${index}][enabled]" value="1" checked></td>
        <td>
            <select class="form-control" name="menu_items[${index}][tab]">
                ${buildTabOptions(defaultTab)}
            </select>
        </td>
        <td><input type="text" class="form-control" name="menu_items[${index}][label]" value=""></td>
        <td>
            <select class="form-control" name="menu_items[${index}][icon]">
                ${buildIconOptions(defaultIcon)}
            </select>
        </td>
        <td><input type="number" class="form-control" name="menu_items[${index}][order]" value="${index}" min="0"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeMenuRow(this)"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(row);
}

function removeMenuRow(btn) {
    const row = btn.closest('tr');
    row.remove();
}
</script>
@endsection
